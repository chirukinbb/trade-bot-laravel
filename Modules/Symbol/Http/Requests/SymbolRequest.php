<?php

namespace Modules\Symbol\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property-read $symbol
 * @property-read $volume
 */
class SymbolRequest extends FormRequest
{
    public function rules()
    {
        return [
            'symbol'=>'string|required',
            'volume'=>'numeric'
            ];
    }
}
