<?php
declare(strict_types=1);

namespace App\Controller;
use App\Traits\WeightedRoundRobin\WeightedRoundRobin;
use Hyperf\Di\Annotation\Inject;
use App\Logic\Order\OrderHandler;

class OrderController extends AbstractController
{

    /**
     * @return array
     * @throws \throwable
     */
    public function create() {
        return $this->orderHandler->create();
    }

    /**
     * @return array
     * @throws \throwable
     */
    public function get() {
        return $this->orderHandler->get();
    }
}
