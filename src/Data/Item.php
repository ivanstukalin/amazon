<?php

namespace App\Data;

class Item
{
    public ?string $itemValue;
    public ?string $description;
    public ?string $itemIdentifier;
    public string  $quantity;
    public array   $weight;
    public ?bool   $isHazmat;
    public ?string $productType;
    public ?array  $serialNumbers;
    public ?array  $directFulfillmentItemIdentifiers;
}