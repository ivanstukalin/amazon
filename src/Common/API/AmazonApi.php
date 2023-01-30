<?php

namespace App\Common;

use Curl\Curl;
use App\Data;
use Exception;

class AmazonApi
{
    private const AMAZON_URL = 'https://sellingpartnerapi-eu.amazon.com/shipping/v2/shipments';

    private const METHOD_RATES = '/rates';
    private const METHOD_ADDITIONAL_INPUTS = '/additionalInputs/schema';

    private ?Curl $_curl;

    /**
     * Getting the rates for a shipment
     * https://developer-docs.amazon.com/amazon-shipping/docs/amazon-shipping-api-v2-use-case-guide#step-1-get-rates-for-a-shipment
     * @param Data\Order $order
     * @return array
     * @throws Exception
     */
    public function getRates(Data\Order $order): array
    {
        $curl = $this->_curl();
        $preparedOrder = [
            'shipTo'         => $this->_convertUserToArray($order->getShipTo()),
            'shipFrom'       => $this->_convertUserToArray($order->getShipFrom()),
            'shipDate'       => $order->getShipDate()->format('Y-m-dTh:m:s'),
            'packges'        => $this->_convertPackagesToArray($order->getPackages()),
            'channelDetails' => $order->getChannelDetails(),
        ];

        $curl->setHeader('x-amzn-shipping-business-id', 'test123');
        $response = $curl->post(self::AMAZON_URL.self::METHOD_RATES, json_encode($preparedOrder));

        if ($curl->error) {
            throw new Exception('Error: ' . $curl->errorMessage . "\n", $curl->errorCode);
        }

        $response = json_decode($response);
        $rates = $response['payload']['rates'] ?? [];
        $preparedRates = [];

        foreach($rates as $rate) {
            $entity                                  = new Entity\Rate();
            $entity->id                              = $rate['rateId'];
            $entity->carrierId                       = $rate['carrierId'];
            $entity->carrierName                     = $rate['carrierName'];
            $entity->serviceId                       = $rate['serviceId'];
            $entity->serviceName                     = $rate['serviceName'];
            $entity->totalCharge                     = $rate['totalCharge'];
            $entity->promise                         = $rate['promise'];
            $entity->supportedDocumentSpecifications = $rate['supportedDocumentSpecifications'];
            $entity->requiresAdditionalInputs        = $rate['requiresAdditionalInputs'];
            $preparedRates[] = $entity;
        }

        return [
            'rates'        => $preparedRates,
            'requestToken' => $response['requestToken'],
        ];
    }

    /**
     * https://developer-docs.amazon.com/amazon-shipping/docs/amazon-shipping-api-v2-use-case-guide#step-2-discover-required-additional-inputs-for-a-shipment
     * @param string $requestToken
     * @return array
     * @throws Exception
     */
    public function getAdditionalInputs(string $requestToken): array
    {
        $curl = $this->_curl();

        $curl->setHeader('x-amzn-shipping-business-id', 'test123');
        $response = $curl->get(self::AMAZON_URL.self::METHOD_ADDITIONAL_INPUTS, [
            'requestToken' => $requestToken
        ]);

        if ($curl->error) {
            throw new Exception('Error: ' . $curl->errorMessage . "\n");
        }

        return $response['properties'] ?? [];
    }

    /**
     * https://developer-docs.amazon.com/amazon-shipping/docs/amazon-shipping-api-v2-use-case-guide#step-3-purchase-a-shipment
     * @param string $requestToken
     * @param Entity\Rate $rate
     * @param Data\DocumentSpecification $documentSpecification
     * @return array
     * @throws Exception
     */
    public function purchaseShipment(
        string $requestToken,
        Entity\Rate $rate,
        Data\DocumentSpecification $documentSpecification
    ): array {
        $curl = $this->_curl();

        $preparedOrder = [
            'requestToken'                   => $requestToken,
            'rateId'                         => $rate->id,
            'requestedDocumentSpecification' => $this->_convertDocumentSpecificationToArray($documentSpecification),
        ];

        $curl->setHeader('x-amzn-shipping-business-id', 'test123');
        $response = $curl->post(self::AMAZON_URL, json_encode($preparedOrder));

        if ($curl->error) {
            throw new Exception('Error: ' . $curl->errorMessage . "\n");
        }

        $response = json_decode($response);

        return $response['payload'] ?? [];
    }

    private function _curl(): Curl
    {
        if ($this->_curl === null) {
            $this->_curl = new Curl();
        }

        return $this->_curl;
    }

    private function _convertUserToArray(Data\User $user): array
    {
        $result = [
            'name'          => $user->name,
            'addressLine1'  => $user->addressLine1,
            'postalCode'    => $user->postalCode,
            'city'          => $user->city,
            'stateOrRegion' => $user->stateOrRegion,
            'countryCode'   => $user->countryCode,
            'email'         => $user->email,
            'phoneNumber'   => $user->phoneNumber
        ];

        if ($user->addressLine2 !== null) {
            $user['addressLine2'] = $user->addressLine2;
        }

        return $result;
    }

    private function _convertPackagesToArray(Data\Package $package): array
    {
        $convertedItems = [];
        foreach ($package->items as $item) {
            $convertedItems = $this->_convertItemToArray($item);
        }

        return [
            'dimensions'               => $this->_convertPackageDimensionsToArray($package->dimension),
            'weight'                   => $package->weight,
            'items'                    => $convertedItems,
            'insuredValue'             => $package->insuredValue,
            'packageClientReferenceId' => $package->clientReferenceId
        ];
    }

    private function _convertPackageDimensionsToArray(Data\PackageDimension $packageDimension): array
    {
        $result = [
            'length'     => $packageDimension->length,
            'width'      => $packageDimension->width,
            'height'     => $packageDimension->height,
            'unit'       => $packageDimension->unit,
        ];
        return $result;
    }

    private function _convertDocumentSpecificationToArray(Data\DocumentSpecification $documentSpecification): array
    {
        return [
            'format'                 => $documentSpecification->format,
            'size'                   => $documentSpecification->size,
            'dpi'                    => $documentSpecification->dpi,
            'pageLayout'             => $documentSpecification->pageLayout,
            'needFileJoining'        => $documentSpecification->needFileJoining,
            'requestedDocumentTypes' => $documentSpecification->requestedDocumentTypes,
        ];
    }

    private function _convertItemToArray(Data\Item $item): array
    {
        $result = [
            'quantity' => $item->quantity,
            'weight'   => $item->weight
        ];

        if ($item->itemValue !== null) {
            $result['itemValue'] = $item->itemValue;
        }

        if ($item->description !== null) {
            $result['description'] = $item->description;
        }

        if ($item->itemIdentifier !== null) {
            $result['itemIdentifier'] = $item->itemIdentifier;
        }

        if ($item->isHazmat !== null) {
            $result['isHazmat'] = $item->isHazmat;
        }

        if ($item->productType !== null) {
            $result['productType'] = $item->productType;
        }

        if ($item->serialNumbers !== null) {
            $result['serialNumbers'] = $item->serialNumbers;
        }

        if ($item->directFulfillmentItemIdentifiers !== null) {
            $result['directFulfillmentItemIdentifiers'] = $item->directFulfillmentItemIdentifiers;
        }

        return $result;
    }
}