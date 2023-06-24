<?php

namespace Modules\Settings\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

class Setting extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'value'
    ];

    public function scopeEnv(\Illuminate\Database\Eloquent\Builder $builder,string $name)
    {
        return $builder->where('name',$name)->first()->value ?? ' ';
    }
}
