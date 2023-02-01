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

    public function __construct(array $data)
    {
        $this->country_id    = $data['country_id'];
        $this->country_code  = $data['country_code'];
        $this->country_code3 = $data['country_code3'];
        $this->name          = $data['name'];
        $this->shop_username = $data['shop_username'];
        $this->email         = $data['email'];
        $this->phone         = $data['phone'];
        $this->address       = $data['address'];
    }

    public static function createById(string $id): Buyer {
        $data = file_get_contents("buyer.{$id}.json");
        $decodedData = json_decode($data);

        return new self($decodedData);
    }

    public function getData(): array
    {
        return [
            'country_id' => $this->country_id,
            'country_code' => $this->country_code,
            'country_code3' => $this->country_code3,
            'name' => $this->name,
            'shop_username' => $this->shop_username,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
        ];
    }

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