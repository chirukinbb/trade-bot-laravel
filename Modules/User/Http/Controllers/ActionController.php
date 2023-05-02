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
        if (auth()->attempt($request->only('password','email'),$request->rememberme === 'on')){
            return redirect()->route('symbols');
        }

        return redirect()->back();
    }
}
