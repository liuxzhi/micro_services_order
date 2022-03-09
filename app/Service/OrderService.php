<?php
declare(strict_types=1);

namespace App\Service;

use App\Contract\OrderServiceInterface;
use App\Model\Order;
use App\Model\Model;

class OrderService extends AbstractService implements OrderServiceInterface
{

    /**
     * @return Model
     */
    public function getModelObject() :Model
    {
        return make(Order::class);
    }
}