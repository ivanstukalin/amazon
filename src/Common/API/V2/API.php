<?php

namespace App\Common\API\V2;

use AmazonPHP\SellingPartner\AccessToken;
use AmazonPHP\SellingPartner\Api\FbaOutboundApi\FulfillmentOutboundSDK;
use AmazonPHP\SellingPartner\Api\NotificationsApi\NotificationsSDK;
use AmazonPHP\SellingPartner\Configuration;
use AmazonPHP\SellingPartner\Exception;
use AmazonPHP\SellingPartner\HttpFactory;
use AmazonPHP\SellingPartner\Model;
use AmazonPHP\SellingPartner\ModelInterface;
use App\Common;
use App\Common\API\AbstractAmazonAPI;
use GuzzleHttp;

class API extends AbstractAmazonAPI
{

    private ?FulfillmentOutboundSDK $_sdk;
    private ?NotificationsSDK $_notification;
    private ?AccessToken $_accessToken;
    private ?string $_region;


    /**
     * @param array $data
     * @return array
     * @throws Common\Exception\API\Api
     * @throws Common\Exception\API\Auth
     * @throws Common\Exception\API\BadRequest
     */
    public function getFulfillmentPreview(array $data): array
    {
        $this->_auth();

        $preparedItems = [];
        foreach ($data['products'] as $item) {
            $preparedItems[] = $this->_prepareItemPreview($item, $data['currency']);
        }

        $preparedData = [
            'marketplace_id'            => $data['marketplace_id'],
            'address'                   => $this->_prepareAddress($data['buyer']['address']),
            'items'                     => $preparedItems,
            'shipping_speed_categories' => $data['shipping_speed_categories'],
        ];

        try {
            $response = $this->_sdk()->getFulfillmentPreview(
                $this->_accessToken,
                $this->_region,
                new Model\FulfillmentOutbound\GetFulfillmentPreviewRequest($preparedData)
            );
        } catch (Exception\ApiException $e) {
            try {
                $response = $this->_retry(
                    array($this->_sdk(), 'getFulfillmentPreview'),
                    $preparedData,
                    $e,
                    5
                );
            } catch (\Exception $e) {
                throw new Common\Exception\API\Api("Amazon API error: {$e->getMessage()}", $e->getCode());
            }
        } catch (Exception\InvalidArgumentException $e) {
            throw new Common\Exception\API\BadRequest(
                "Bad params in get fulfillment preview request, {$e->getMessage()}"
            );
        }

        $result = [];
        foreach ($response->getPayload()->getFulfillmentPreviews() as $fulfillmentPreview) {
            $result = $fulfillmentPreview->getFulfillmentPreviewShipments();
        }

        return $result;
    }

    /**
     * @param array $data
     * @param array $fulfillmentPreview
     * @return void
     * @throws Common\Exception\API\Api
     * @throws Common\Exception\API\Auth
     * @throws Common\Exception\API\BadRequest
     */
    public function createFulfillmentOrder(array $data, array $fulfillmentPreview): void
    {
        $this->_auth();
        $preparedItems = [];
        foreach ($data['items'] as $item) {
            $preparedItems[] = $this->_prepareItem($item, $data['currency']);
        }

        $preparedData = [
            'marketplace_id'              => $data['marketplace_id'],
            'seller_fulfillment_order_id' => $data['order_unique'],
            'displayable_order_id'        => $data['order_unique'],
            'displayable_order_date'      => $data['order_date'],
            'shipping_speed_category'     => new Model\FulfillmentOutbound\ShippingSpeedCategory($data['speed_category']),
            'delivery_window'             => new Model\FulfillmentOutbound\DeliveryWindow([
                'start_date' => $fulfillmentPreview['earliest_arrival_date'],
                'end_date'   => $fulfillmentPreview['latest_arrival_date'],
            ]),
            'destination_address'         => $this->_prepareAddress($data['buyer']['address']),
            'items'                       => $preparedItems,
        ];

        try {
            $fulfillmentOrder = $this->_sdk()->createFulfillmentOrder(
                $this->_accessToken,
                $this->_region,
                new Model\FulfillmentOutbound\CreateFulfillmentOrderRequest($preparedData)
            );
            $errors = $fulfillmentOrder->getErrors();
            if ($errors !== null && count($errors) !== 0) {
                $encodedErrors = json_encode($errors);
                throw new Exception\ApiException("Error in create fulfillment order request: {$encodedErrors}");
            }
        } catch (Exception\ApiException $e) {
            throw new Common\Exception\API\Api("Amazon API error: {$e->getMessage()}", $e->getCode());
        } catch (Exception\InvalidArgumentException $e) {
            throw new Common\Exception\API\BadRequest("Bad params in get fulfillment preview request, {$e->getMessage()}");
        }
    }

    /**
     * @param string $name
     * @param string $accountId
     * @return string
     * @throws Common\Exception\API\Api
     * @throws Common\Exception\API\Auth
     * @throws Common\Exception\API\BadRequest
     */
    public function createDestination(string $name, string $accountId): string {
        $this->_auth();
        try {
            $destination = $this->_getNotificationSDK()->createDestination(
                $this->_accessToken,
                $this->_region,
                new Model\Notifications\CreateDestinationRequest([
                    'resource_specification' => [
                        'event_bridge' => new Model\Notifications\EventBridgeResourceSpecification([
                            'region' => $this->_region,
                            'account_id' => $accountId
                        ]),
                    ],
                    'name' => $name
                ])
            );
        } catch (Exception\InvalidArgumentException $e) {
            throw new Common\Exception\API\BadRequest("Bad params in create destination request, {$e->getMessage()}");
        } catch (Exception\ApiException $e) {
            throw new Common\Exception\API\Api("Amazon API error: {$e->getMessage()}", $e->getCode());
        }

        return $destination->getPayload()->getDestinationId();
    }

    public function createSubscription(string $notificationType, string $destinationId): void {
        $this->_auth();

        try {
            $this->_getNotificationSDK()->createSubscription(
                $this->_accessToken,
                $this->_region,
                $notificationType,
                new Model\Notifications\CreateSubscriptionRequest([
                    'payload_version' => '1.0',
                    'destination_id' => $destinationId
                ])
            );
        } catch (Exception\InvalidArgumentException $e) {
            throw new Common\Exception\API\BadRequest("Bad params in create subscription request, {$e->getMessage()}");
        } catch (Exception\ApiException $e) {
            throw new Common\Exception\API\Api("Amazon API error: {$e->getMessage()}", $e->getCode());
        }
    }

    /**
     * @param string $orderId
     * @return string
     * @throws Common\Exception\API\Api
     * @throws Common\Exception\API\Auth
     * @throws Common\Exception\API\BadRequest
     */
    public function getFulfillmentOrderTrackingNumber(string $orderId): string {
        $this->_auth();
        try {
            $fulfillmentOrder = $this->_sdk()->getFulfillmentOrder(
                $this->_accessToken,
                $this->_region,
                $orderId
            );
        } catch (Exception\InvalidArgumentException $e) {
            throw new Common\Exception\API\BadRequest("Bad params in get fulfillment order request, {$e->getMessage()}");
        } catch (Exception\ApiException $e) {
            try {
                $fulfillmentOrder = $this->_retry(
                    array($this->_sdk(), 'getFulfillmentOrder'),
                    [$this->_accessToken, $this->_region, $orderId],
                    $e,
                    5
                );
            } catch (\Exception $e) {
                throw new Common\Exception\API\Api("Amazon API error: {$e->getMessage()}", $e->getCode());
            }
        }
        $trackingNumber = 0;
        foreach ($fulfillmentOrder->getPayload()->getFulfillmentShipments() as $fulfillmentShipment) {
            foreach ($fulfillmentShipment->getFulfillmentShipmentPackage() as $fulfillmentShipmentPackage) {
                $trackingNumber = $fulfillmentShipmentPackage->getTrackingNumber();
            }
        }

        return $trackingNumber;
    }

    public function setRegion(string $region): void {
        $this->_region = $region;
    }

    private function _auth(): void
    {
        if ($this->_accessToken === null) {
            try {
                // TO DO: add some auth logic
                $this->_accessToken = AccessToken::fromJSON(json_encode(TEST_AMAZON_USER_DATA), "test");
            } catch (\Exception $e) {
                throw new Common\Exception\API\Auth($e->getMessage());
            }
        }

        $currentTime = new \DateTime();
        if ($this->_accessToken->expiresIn() <= $currentTime->getTimestamp()) {
            // TO DO: add some refresh logic
            $this->_accessToken = AccessToken::fromJSON(json_encode(TEST_AMAZON_USER_DATA), "test");
        }
    }

    /**
     * @param $request
     * @param \Exception $exceptionForRetry
     * @param int $counter
     * @return void
     * @throws \Exception
     */
    private function _retry($request, $params, \Exception $exceptionForRetry, int $counter): ModelInterface
    {
        try {
            $result = call_user_func_array($request, $params);
        } catch(\Exception $e) {
            if ($e instanceof $exceptionForRetry && $counter >= 1) {
                return $this->_retry($request, $params, $exceptionForRetry, $counter-1);
            } else {
                throw $exceptionForRetry;
            }
        }
        return $result;
    }

    private function _prepareAddress(array $data): Model\FulfillmentOutbound\Address {
        return new Model\FulfillmentOutbound\Address([
            'address_line1' => $data['address'],
            'country_code'  => $data['country_code'],
            'phone'         => $data['phone'],
        ]);
    }

    private function _prepareItemPreview(array $itemData, string $currencyCode): Model\FulfillmentOutbound\GetFulfillmentPreviewItem
    {
        return new Model\FulfillmentOutbound\GetFulfillmentPreviewItem([
            'seller_sku' => $itemData['sku'],
            'per_unit_declared_value' => new Model\FulfillmentOutbound\Money([
                'currency_code' => $currencyCode,
                'value' => $itemData['buying_price']
            ]),
            'seller_fulfillment_order_item_id' => $itemData['order_product_id'],
        ]);
    }

    private function _prepareItem(array $itemData, string $currencyCode)
    {
        return new Model\FulfillmentOutbound\CreateFulfillmentOrderItem([
            'seller_sku' => $itemData['sku'],
            'seller_fulfillment_order_item_id' => $itemData['order_product_id'],
            'quantity' => $itemData['ammount'],
            'displayable_comment' => $itemData['comment'],
        ]);
    }

    private function _sdk(?string $sdkName = FulfillmentOutboundSDK::class): FulfillmentOutboundSDK
    {
        if ($this->_sdk === null) {
            $this->_sdk = new $sdkName(
                new GuzzleHttp\Client(),
                new HttpFactory(
                    new Common\Factory\RequestFactory(),
                    new Common\Factory\StreamFactory()
                ),
                new Configuration(
                    'test',
                    'test',
                    'test',
                    'test'
                ),
                new Common\AbstractLogger()
            );
        }
        return $this->_sdk;
    }

    private function _getNotificationSDK(): NotificationsSDK {
        if ($this->_notification === null) {
            $this->_notification = new NotificationsSDK(
                new GuzzleHttp\Client(),
                new HttpFactory(
                    new Common\Factory\RequestFactory(),
                    new Common\Factory\StreamFactory()
                ),
                new Configuration(
                    'test',
                    'test',
                    'test',
                    'test'
                ),
                new Common\AbstractLogger()
            );
        }
        return $this->_notification;
    }
}