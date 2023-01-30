<?php
require 'vendor/autoload.php';

$buyerData = file_get_contents('mock/buyer.29664.json');
$orderData = file_get_contents('mock/order.16400.json');

$buyer = new App\Data\Buyer();
$buyer->name  = $buyerData['shop_username'];
$buyer->email = $buyerData['email'];

$customer = new App\Data\User();

$items = [];
foreach ($orderData['products'] as $product) {
    $item = new \App\Data\Item();
    // TO DO: Item creating logic
    $items[] = $item;
}

$packageDimension = new \App\Data\PackageDimension();
// TO DO: Package dimension creating logic

$package = new \App\Data\Package();
$package->dimension = $packageDimension;

$currentDate     = new DateTime();
$order           = new App\Data\Order($orderData['order_id'], $customer, $currentDate, $package, ['channelType' => 'EXTERNAL']);
$shippingService = new \App\ShippingService();
$trackingNumber  = "";
try {
    $trackingNumber = $shippingService->ship($order, $buyer);
} catch (Exception $e) {
    echo 'Amazon api service is unavailable now.';
}

if ($trackingNumber !== '') {
    echo $trackingNumber.PHP_EOL;
}
