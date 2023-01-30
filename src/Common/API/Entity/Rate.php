<?php

namespace App\Common\Entity;

class Rate
{
    public string $id;
    public string $carrierId;
    public string $carrierName;
    public string $serviceId;
    public string $serviceName;
    public string $totalCharge;
    public string $promise;
    public array  $supportedDocumentSpecifications;
    public bool   $requiresAdditionalInputs;
}