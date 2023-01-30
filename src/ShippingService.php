<?php

namespace App;

use App\Data;
use App\Common;


class ShippingService implements ShippingServiceInterface
{
    private ?Common\AmazonApi $_api;

    /**
     * @param Data\AbstractOrder $order
     * @param Data\BuyerInterface $buyer
     * @return string
     * @throws \Exception
     */
    public function ship(Data\AbstractOrder $order, Data\BuyerInterface $buyer): string
    {
        $api = $this->_getApi();
        $this->_setBuyer($order, $buyer);

        try {
            $rates  = $api->getRates($order);
            $requestToken = $rates['requestToken'];
            $rate   = $this->_getRateId($rates['rates']);
            if ($rate->requiresAdditionalInputs) {
                $additionalInputs = $api->getAdditionalInputs($requestToken);
                if (count($additionalInputs) > 0) {
                    $decodedAdditionalInputs = json_encode($additionalInputs);
                    Common\AbstractEmailer::sendMessage("Please, set additional inputs to order: {$decodedAdditionalInputs}");
                }
            }
            $purchasedShipment = $api->purchaseShipment($requestToken, $rate, $this->_getBaseDocumentSpecification());
            return $purchasedShipment['packageDocumentDetails']['trackingId'] ?? '';
        } catch (\Exception $e) {
            switch($e->getCode()) {
                case 403:
                    Common\AbstractLogger::log("Auth error: {$e->getMessage()}");
                    break;
                default:
                    Common\AbstractLogger::log("Error: {$e->getMessage()}");
            }
            throw new \Exception();
        }
    }

    private function _setBuyer(Data\Order $order, Data\BuyerInterface $buyer): void {
        $user = new Data\User();
        $user->name          = $buyer->name;
        $user->addressLine1  = $buyer->address;
        $user->phoneNumber   = $buyer->phone;
        $user->email         = $buyer->email;
        $user->countryCode   = $buyer->country_code;
        $user->stateOrRegion = $buyer->country_id;
        $user->postalCode    = $buyer->country_code3;

        $order->setShipTo($user);
    }

    /**
     * @param array $rates
     * @return Common\Entity\Rate
     */
    private function _getRateId(Array $rates): Common\Entity\Rate {
        $rateNum = rand(0, count($rates)-1);
        return $rates[$rateNum];
    }

    /**
     * @return Common\AmazonApi
     */
    private function _getApi(): Common\AmazonApi {
        if ($this->_api === null) {
            $this->_api = new Common\AmazonApi();
        }

        return $this->_api;
    }

    /**
     * @return Data\DocumentSpecification
     */
    private function _getBaseDocumentSpecification(): Data\DocumentSpecification {
        $documentSpecification                          = new Data\DocumentSpecification();
        $documentSpecification->format                 = 'PNG';
        $documentSpecification->size                   = [
            'width'  => 4,
            'length' => 6,
            'unit'   => 'INCH',
        ];
        $documentSpecification->dpi                    = 300;
        $documentSpecification->pageLayout             = "DEFAULT";
        $documentSpecification->needFileJoining        = false;
        $documentSpecification->requestedDocumentTypes = ["LABEL"];
        return $documentSpecification;
    }
}