<?php
declare(strict_types=1);

namespace App\Controller;
use App\Traits\WeightedRoundRobin\WeightedRoundRobin;
use Hyperf\Di\Annotation\Inject;
use App\Logic\Order\OrderHandler;

class IndexController extends AbstractController
{
    use WeightedRoundRobin;

    /**
     * @Inject
     * @var orderHandler
     */
    public $orderHandler;

    public function index()
    {
        return $this->weight();
    }
}
