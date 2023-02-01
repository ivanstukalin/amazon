<?php

namespace App\Data;

class Order extends AbstractOrder
{
    public function loadOrderData(int $id): array
    {
        $orderJSON = file_get_contents("mock/order.{$id}.json");

        return json_decode($orderJSON, true);
    }
}