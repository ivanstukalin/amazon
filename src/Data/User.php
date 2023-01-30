<?php

namespace App\Data;

class User
{
    public string  $name;
    public string  $addressLine1;
    public ?string $addressLine2;
    public string  $postalCode;
    public string  $city;
    public string  $stateOrRegion;
    public string  $countryCode;
    public string  $email;
    public string  $phoneNumber;
}