<?php
declare(strict_types=1);

namespace App\Controller;
use App\Traits\WeightedRoundRobin\WeightedRoundRobin;
use Hyperf\Di\Annotation\Inject;

class IndexController extends AbstractController
{
    use WeightedRoundRobin;

    public function index()
    {
        return $this->weight();
    }
}
