<?php

namespace Modules\Symbol\Entities;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

/**
 * Modules\Symbol\Entities\Symbol
 *
 * @property int $id
 * @property string $name
 * @method static \Illuminate\Database\Eloquent\Builder|Symbol newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Symbol newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Symbol query()
 * @method static \Illuminate\Database\Eloquent\Builder|Symbol whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Symbol whereName($value)
 * @mixin \Eloquent
 */
class Symbol extends Model
{
    public $timestamps = false;

    protected $fillable = ['name'];
}
