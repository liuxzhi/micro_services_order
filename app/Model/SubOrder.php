<?php

declare (strict_types=1);
namespace App\Model;

/**
 * @property int $sub_trade_no 
 * @property int $trade_no 
 */
class SubOrder extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sub_order_shard';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['sub_trade_no', 'trade_no'];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['sub_trade_no' => 'integer', 'trade_no' => 'integer'];
}