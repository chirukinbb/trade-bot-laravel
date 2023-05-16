<?php

namespace Modules\User\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\User\Http\Requests\LoginRequest;

class ActionController extends Controller
{
    public function login(LoginRequest $request)
    {
        if (auth()->attempt($request->only('password','email'),$request->remember === 'on')){
            return redirect()->route('dashboard');
        }

        return redirect()->back();
    }

    public function logout()
    {
        \Auth::logout();

        return redirect()->route('login');
    }
}
