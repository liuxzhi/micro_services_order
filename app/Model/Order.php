<?php

declare (strict_types=1);
namespace App\Model;

/**
 * @property int $trade_no 
 * @property string $body 
 * @property int $total_fee 
 * @property string $spbill_create_ip 
 * @property string $wx_appid 
 * @property string $open_id 
 * @property int $state 
 * @property string $agg_order_id 
 * @property string $pay_data 
 * @property int $order_at 
 * @property int $end_at 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 * @property int $deleted_at 
 */
class Order extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'order_shard';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['trade_no', 'body', 'total_fee', 'spbill_create_ip', 'wx_appid', 'open_id', 'state', 'agg_order_id', 'pay_data'];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['trade_no' => 'integer', 'total_fee' => 'integer', 'state' => 'integer'];

    public $incrementing = false;
    protected $primaryKey = 'trade_no';
}