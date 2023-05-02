<?php

namespace Modules\Symbol\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Lin\Binance\Binance;
use Lin\Bitget\BitgetSwap;
use Lin\Bybit\BybitLinear;
use Lin\Bybit\BybitSpot;
use Lin\Gate\GateSpotV2;
use Lin\Ku\Kucoin;
use Lin\Mxc\MxcSpot;
use Modules\Symbol\Entities\Symbol;
use Modules\User\Entities\User;
use function Symfony\Component\Translation\t;

class PageController extends Controller
{
    public function index()
    {
        $token = Auth::user()->createToken('user')->plainTextToken;

        return view('symbol::index',compact('token'));
    }
}
