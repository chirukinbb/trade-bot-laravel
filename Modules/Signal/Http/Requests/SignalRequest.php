<?php

namespace Modules\Signal\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property-read $symbol
 */
class SignalRequest extends FormRequest
{
    public function rules()
    {
        return ['symbol'=>'string'];
    }
}
