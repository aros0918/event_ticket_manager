<?php

namespace App\Http\Controllers\Voyager;

use TCG\Voyager\Http\Controllers\VoyagerController as BaseVoyagerController;

use Auth;

class VoyagerController extends BaseVoyagerController
{
    public function index()
    {
        return view('vendor.voyager.dashboard');
    }

    public function logout()
    {
        Auth::logout();

        return redirect(config('custom.route.prefix') . '/' . config('custom.route.admin_prefix'));
    }
}
