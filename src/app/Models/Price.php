<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperPrice
 */
class Price extends Model
{
    protected $fillable = [
        'game_id',
        'currency',
        'initial_price',
        'final_price',
        'discount_percent',
    ];
    public $timestamps = false;


}
