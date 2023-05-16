<?php

namespace Modules\Signal\Entities;

use Illuminate\Database\Eloquent\Concerns\HasRelationships;
use Illuminate\Database\Eloquent\Model;

/**
 * Modules\Signal\Entities\Signal
 *
 * @property int $id
 * @property string $base_coin
 * @property string $quote_coin
 * @property array $buy_prices
 * @property array $sell_prices
 * @property array $buy_volumes
 * @property array $sell_volumes
 * @property string $sell_exchange
 * @property string $buy_exchange
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Signal newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Signal newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Signal query()
 * @method static \Illuminate\Database\Eloquent\Builder|Signal whereBaseCoin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Signal whereBuyPrices($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Signal whereBuyVolumes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Signal whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Signal whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Signal whereQuoteCoin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Signal whereSellPrices($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Signal whereSellVolumes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Signal whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Signal extends Model
{
    use HasRelationships;

    protected $fillable = [
        'base_coin',
        'quote_coin',
        'buy_prices',
        'sell_prices',
        'buy_volumes',
        'sell_volumes',
        'sell_exchange',
        'buy_exchange'
    ];

    protected $casts = [
        'buy_prices'=>'array',
        'sell_prices'=>'array',
        'buy_volumes'=>'array',
        'sell_volumes'=>'array',
    ];

    public function profit(bool $coinSymbol = true)
    {
        return number_format($this->sell_volumes[1] - $this->buy_volumes[1],8).($coinSymbol ? $this->quote_coin : '');
    }

    public function spread()
    {
        return number_format(100*(($this->sell_volumes[1] - $this->buy_volumes[1]) / $this->sell_volumes[1]),3).'%';
    }

    public function deals()
    {
        return $this->hasMany(Deal::class);
    }
}
