<?php

namespace Modules\Settings\Http\Controllers;

class PageController extends Controller
{
    public function index()
    {
        return view('settings::index',['fields'=>$this->fields]);
    }
}
