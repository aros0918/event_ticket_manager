<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

use Auth;

class CustomController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // language change
        $this->middleware('common');
    }

    public function logout()
    {
        Auth::logout();

        $redirect = !empty(config('custom.route.prefix')) ? config('custom.route.prefix') : '/';
        return redirect($redirect);
    }

    public function assets(Request $request)
    {
        if (class_exists('\Str'))
            $path = \Str::start(str_replace(['../', './'], '', urldecode($request->path)), DIRECTORY_SEPARATOR);
        else
            $path = str_start(str_replace(['../', './'], '', urldecode($request->path)), DIRECTORY_SEPARATOR);

        $path = base_path('public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . $path);

        if (File::exists($path)) {
            $mime = '';

            if (class_exists('\Str')) {
                if (\Str::endsWith($path, '.js')) {
                    $mime = 'text/javascript';
                } elseif (\Str::endsWith($path, '.css')) {
                    $mime = 'text/css';
                } else {
                    $mime = File::mimeType($path);
                }
            } else {
                if (ends_with($path, '.js')) {
                    $mime = 'text/javascript';
                } elseif (ends_with($path, '.css')) {
                    $mime = 'text/css';
                } else {
                    $mime = File::mimeType($path);
                }
            }

            $response = response(File::get($path), 200, ['Content-Type' => $mime]);
            $response->setSharedMaxAge(31536000);
            $response->setMaxAge(31536000);
            $response->setExpires(new \DateTime('+1 year'));

            return $response;
        }

        return response('', 404);
    }


    public function change_lang($lang = null)
    {
        \Session::put('my_lang', $lang);

        return redirect($_SERVER['HTTP_REFERER']);
    }


}
