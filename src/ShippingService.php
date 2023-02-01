<?php

namespace App;

use App\Common\AbstractEmailer;
use App\Common\AbstractLogger;
use App\Common\API\AbstractAmazonAPI;
use App\Common\Exception\API;
use App\Data;
use App\Common\API\V2;
use AmazonPHP\SellingPartner\Model;
use DateTime;

class ShippingService implements ShippingServiceInterface
{
    private ?AbstractAmazonAPI $_api;
    private ?AbstractLogger $_logger;

    private const AVAILABLE_SHIP_SPEED_CATEGORIES = [
        'Standard',
        'Expedited',
        'Priority',
        'ScheduledDelivery',
    ];

    private const NOTIFICATION_TYPE_FULFILLMENT_ORDER_STATUS = 'FULFILLMENT_ORDER_STATUS';

    /**
     * @param Data\AbstractOrder $order
     * @param Data\BuyerInterface $buyer
     * @return string
     * @throws \Exception
     */
    public function ship(Data\AbstractOrder $order, Data\BuyerInterface $buyer): string
    {
        $this->_api->setRegion(TEST_AMAZON_REGION);
        $order->load();

        try {
            $orderData                              = array_merge($order->data, $buyer->getData());
            $orderData['marketplace_id']            = TEST_MARKETPLACE_ID;
            $orderData['shipping_speed_categories'] = self::AVAILABLE_SHIP_SPEED_CATEGORIES;
            $fulfillmentPreviews                    = $this->_api->getFulfillmentPreview($orderData);

            $this->_api->createFulfillmentOrder(
                $this->_getFastestFulfillmentPreview($fulfillmentPreviews),
                $order->data
            );

            $destinationId = $this->_api->createDestination(
                "ORDER_{$order->getOrderId()}_STATUS_CHANGE",
                TEST_ACCOUNT_ID
            );
            $this->_api->createSubscription(self::NOTIFICATION_TYPE_FULFILLMENT_ORDER_STATUS, $destinationId);
            //waiting for order status change on Amazon server and get webhook with info about that ....
            return $this->getTrackingNumber($order);
        } catch (API\Auth $e) {
            $this->_logger()->error("Amazon API AUTH error: {$e->getMessage()}");
            AbstractEmailer::sendMessage(
                "Got Amazon API AUTH error: {$e->getMessage()}. Need fast fix this problem",
                TEST_ADMIN_EMAIL
            );
            throw new \Exception();
        } catch (API\Api $e) {
            $this->_logger()->warning("Amazon API exception: {$e->getMessage()}");
            throw new \Exception();
        } catch (API\BadRequest $e) {
            $this->_logger()->error("Error in Amazon API request: {$e->getMessage()}");
            throw new \Exception();
        }
    }

    /**
     * @param Data\AbstractOrder $order
     * @return string
     * @throws API\BadRequest|API\Auth|API\Api
     */
    public function getTrackingNumber(Data\AbstractOrder $order): string
    {
        return $this->_api->getFulfillmentOrderTrackingNumber($order->getOrderId());
    }

    /**
     * Set api service that will be used for command sending to Amazon's fulfillment network
     * @param AbstractAmazonAPI $api
     * @return void
     */
    public function setApi(AbstractAmazonAPI $api): void {
        $this->_api = $api;
    }

    /**
     * Choose the fastest preview for delivering
     * @param array $fulfillmentPreviews
     * @return array|null
     */
    private function _getFastestFulfillmentPreview(array $fulfillmentPreviews): array
    {
        array_filter($fulfillmentPreviews, function (array $fulfillmentPreview) {
            $shippingSpeedCategory = $fulfillmentPreview['shipping_speed_category'] ?? null;
            return in_array($shippingSpeedCategory, self::AVAILABLE_SHIP_SPEED_CATEGORIES);
        });

        $currentFulfillmentPreview = $fulfillmentPreviews[0] ?? null;
        $latestArrivalDate         = new DateTime(
            $currentFulfillmentPreview['fulfillment_preview_shipments']['earliest_arrival_date']
        );

        foreach ($fulfillmentPreviews as $fulfillmentPreview) {
            $fulfillmentPreviewLatestArrivalDate = new DateTime(
                $fulfillmentPreview['fulfillment_preview_shipments']['earliest_arrival_date']
            );
            // find the earliest arrival date in worst case (latestArrivalDate param)
            if ($latestArrivalDate->getTimestamp() > $fulfillmentPreviewLatestArrivalDate->getTimestamp()) {
                $currentFulfillmentPreview = $fulfillmentPreview;
            }
        }

        return $currentFulfillmentPreview;
    }

    /**
     * @return AbstractLogger
     */
    private function _logger(): AbstractLogger
    {
        if ($this->_logger === null) {
            $this->_logger = new AbstractLogger();
        }

        return $this->_logger;
    }
}