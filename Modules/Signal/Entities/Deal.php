<?php

namespace Modules\Signal\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Modules\Deal\Entities\Deal
 *
 * @property int $id
 * @property string $exchange
 * @property string $exchange_id
 * @property int $signal_id
 * @method static \Illuminate\Database\Eloquent\Builder|Deal newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Deal newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Deal query()
 * @method static \Illuminate\Database\Eloquent\Builder|Deal whereExchange($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deal whereExchangeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deal whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deal whereSignalId($value)
 * @mixin \Eloquent
 */
class Deal extends Model
{
    protected $fillable = [
        'exchange',
        'exchange_id',
        'signal_id'
    ];
}
