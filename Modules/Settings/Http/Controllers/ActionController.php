<?php

namespace Modules\Settings\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Settings\Entities\Setting;

class ActionController extends Controller
{
    public function save(Request $request)
    {
        foreach ($request->all() as $name=>$value) {
            Setting::updateOrCreate(['name'=>$name],['value'=>$value]);
        }

        return redirect()->back();
    }
}
