<?php

namespace Modules\Dashboard\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property-read int $period
 */
class DashboardRequest extends FormRequest
{
    public function rules()
    {
        return ['period'=>'numeric'];
    }
}
