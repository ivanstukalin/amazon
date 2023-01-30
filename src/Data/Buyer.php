<?php

namespace App\Data;

class Buyer implements BuyerInterface
{

    public int $country_id;
    public string $country_code;
    public string $country_code3;
    public string $name;
    public string $shop_username;
    public string $email;
    public string $phone;
    public string $address;
    public array $data;

    /**
     * @inheritDoc
     */
    public function offsetExists($offset)
    {
        // TODO: Implement offsetExists() method.
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset)
    {
        // TODO: Implement offsetGet() method.
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value)
    {
        // TODO: Implement offsetSet() method.
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset)
    {
        // TODO: Implement offsetUnset() method.
    }
}