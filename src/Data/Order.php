<?php

namespace App\Data;

class Order extends AbstractOrder
{
    private ?User      $_shipTo;
    private User      $_shipFrom;
    private \DateTime $_shipDate;
    private Package   $_packages;
    private array     $_channelDetails;

    public function __construct(int $id, $shipFrom, $shipDate, $packages, $channelDetails, $shipTo = null)
    {
        parent::__construct($id);
        $this->_shipTo         = $shipTo;
        $this->_shipFrom       = $shipFrom;
        $this->_shipDate       = $shipDate;
        $this->_packages       = $packages;
        $this->_channelDetails = $channelDetails;
    }

    public function setShipTo(User $buyer): void{

    }

    public function getShipTo(): User {
        return $this->_shipTo;
    }

    public function getShipFrom(): User {
        return $this->_shipFrom;
    }

    public function getShipDate(): \DateTime {
        return $this->_shipDate;
    }

    public function getPackages(): Package {
        return $this->_packages;
    }

    public function getChannelDetails(): array {
        return $this->_channelDetails;
    }

    protected function loadOrderData(int $id): array
    {
        // TODO: Implement loadOrderData() method.
        return [];
    }
}