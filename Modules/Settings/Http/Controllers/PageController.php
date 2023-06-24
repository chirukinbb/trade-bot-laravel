<?php

namespace Modules\Settings\Http\Controllers;

use Modules\Settings\Entities\Setting;

class PageController extends Controller
{
    public function index()
    {//dd(Setting::env('CHECK_WITHDRAWAL'));
        return view('settings::index',['fields'=>$this->fields]);
    }
}
