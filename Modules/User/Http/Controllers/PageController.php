<?php

namespace Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;

class PageController extends Controller
{
    public function login()
    {
        return view('user::login');
    }
}
