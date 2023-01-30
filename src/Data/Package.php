<?php

namespace App\Data;

class Package
{
    public PackageDimension $dimension;
    public array            $weight;
    public array            $items;
    public array            $insuredValue;
    public string           $clientReferenceId;
}