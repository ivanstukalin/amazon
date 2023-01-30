<?php

namespace App;

use App\Data\AbstractOrder;
use App\Data\BuyerInterface;

class ShippingService implements ShippingServiceInterface
{

    /**
     * @inheritDoc
     */
    public function ship(AbstractOrder $order, BuyerInterface $buyer): string
    {
        // TODO: Implement ship() method.
    }
}