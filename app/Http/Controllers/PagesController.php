<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Models\Page;


class PagesController extends Controller
{

    public function __construct()
    {
        // language change
        $this->middleware('common');
    }

    // get featured events
    public function view($page = null, $view = 'pages', $extra = [])
    {
        $page = Page::where(['slug' => $page])->firstOrFail();
        return view($view, compact('page', 'extra'));
    }
}