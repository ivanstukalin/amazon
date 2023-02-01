<?php
require 'vendor/autoload.php';

$order           = new \App\Data\Order('16400');
$buyer           = \App\Data\Buyer::createById('29664');
$shippingService = new \App\ShippingService();

$shippingService->setApi(new \App\Common\API\V2\API());

$trackingNumber = null;
try {
    $trackingNumber = $shippingService->ship($order, $buyer);
} catch (Exception $e) {
    echo 'Amazon api service is unavailable now.';
}

if ($trackingNumber !== null) {
    echo $trackingNumber.PHP_EOL;
}
