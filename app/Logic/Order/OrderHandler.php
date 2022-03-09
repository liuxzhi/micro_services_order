<?php

declare(strict_types=1);

namespace App\Logic\Order;

use App\Helper\Log;
use Hyperf\DbConnection\Db;
use App\Model\Order;
use App\Model\SubOrder;
use App\Logic\IdGenerate;
use \throwable;


class OrderHandler
{
    use IdGenerate;

    /**
     * @param $params
     *
     * @return array
     * @throws throwable
     */
    public function create(): array
    {
        $tradeNo = $this->generate();
        try {
            Db::beginTransaction();
            Order::insert(["trade_no" => $tradeNo]);
            SubOrder::insert(["trade_no" => $tradeNo, "sub_trade_no" => time()]);
            Db::commit();
        } catch (throwable $throwable) {
            Db::rollBack();
            Log::error("create_order_error", ["message" => $throwable->getMessage()]);
            throw $throwable;
        }

        return ["trade_no" => $tradeNo];
    }

    /**
     * @param $params
     *
     * @return array
     * @throws throwable
     */
    public function get(): array
    {
        $result = Db::select("SELECT * FROM order_shard WHERE trade_no = 1501442144269766656");
        return ["result" => $result];
    }
}