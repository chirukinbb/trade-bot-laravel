<?php

namespace Modules\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property-read $email
 * @property-read $password
 * @property-read $rememberme
 */
class LoginRequest extends FormRequest
{
    public function rules()
    {
        return [
            'email'=>'email|required',
            'password'=>'string|required',
            'remember'=>'string'
        ];
    }
}
