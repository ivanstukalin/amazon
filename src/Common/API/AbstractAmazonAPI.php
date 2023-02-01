<?php

namespace App\Common\API;

class AbstractAmazonAPI
{
    public function getFulfillmentPreview(array $data): array
    {
        return [];
    }

    public function createFulfillmentOrder(array $data, array $fulfillmentPreview): void
    {
        return;
    }

    public function createDestination(string $name, string $accountId): string
    {
        return '';
    }

    public function createSubscription(string $notificationType, string $destinationId): void
    {
        return;
    }

    public function getFulfillmentOrderTrackingNumber(string $orderId): string
    {
        return '';
    }

    public function setRegion(string $region): void
    {

    }
}