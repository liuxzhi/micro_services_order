<?php
declare(strict_types=1);

namespace App\Service;

use App\Contract\SubOrderServiceInterface;
use App\Model\SubOrder;
use App\Model\Model;


class AttributeValueService extends AbstractService implements SubOrderServiceInterface
{
    /**
     * @return AttributeValue|mixed
     */
    public function getModelObject() :Model
    {
        return make(SubOrder::class);
    }
}