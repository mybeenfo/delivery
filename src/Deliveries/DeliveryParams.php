<?php

namespace Monastirevrf\DeliveryService\Service\Deliveries;

use Monastirevrf\DeliveryService\Exceptions\DeliveriesException;
use Symfony\Component\HttpFoundation\Request;

class DeliveryParams
{
    /**
     * @var string
     */
    private $deliveryCode;

    /**
     * @var array
     */
    private $geoParams;

    /**
     * @var array
     */
    private $tradePointsFromRequest;

    /**
     * @var float
     */
    private $orderAmount;

    /**
     * @array
     */
    private $addressCoordinates;

    /**
     * @string
     */
    private $desiredDate;

    /**
     * @string
     */
    private $clientPostcode;

    /**
     * @throws DeliveriesException
     */
    public function __construct(Request $request)
    {
        $geoParams = [
            'federalDistrict' => $request->get('federalDistrict') ?? '',
            'regionKladrId' => $request->get('regionKladrId') ?? '',
            'cityKladrId' => $request->get('cityKladrId') ?? '',
            'cityArea' => $request->get('cityArea') ?? '',
        ];

        $this
            ->setDeliveryCode($request->get('deliveryCode'))
            ->setGeoParams($geoParams)
            ->setTradePointsFromRequest($request->get('tradePointsWithReadyTimes'))
            ->setOrderAmount($request->get('orderAmount'))
            ->setAddressCoordinates($request->get('addressCoordinates') ?? '')
            ->setDesiredDate($request->get('desiredDate') ?? '')
            ->setClientPostcode($request->get('clientPostcode') ?? '');
    }


    public function getDeliveryCode(): string
    {
        return $this->deliveryCode;
    }


    /**
     * @throws DeliveriesException
     */
    public function setDeliveryCode(string $deliveryCode): self
    {
        if (empty($deliveryCode))
            throw new DeliveriesException('Error: deliveryCode parameter is required.');

        $this->deliveryCode = $deliveryCode;

        return $this;
    }


    public function getGeoParams(): array
    {
        return $this->geoParams;
    }


    /**
     * @throws DeliveriesException
     */
    public function setGeoParams(array $geoParams): self
    {
        if (empty($geoParams['cityKladrId'])) {
            throw new DeliveriesException('cityKladrId parameter is required.');
        }

        $this->geoParams = $geoParams;

        return $this;
    }


    public function getTradePointsFromRequest(): array
    {
        return $this->tradePointsFromRequest;
    }


    /**
     * @throws DeliveriesException
     */
    public function setTradePointsFromRequest(array $tradePointsFromRequest): self
    {
        if (empty($tradePointsFromRequest)) {
            throw new DeliveriesException('Error: tradePointsWithReadyTimes parameter is required and must be array.');
        }

        $this->tradePointsFromRequest = $tradePointsFromRequest;

        return $this;
    }


    public function getOrderAmount(): float
    {
        return $this->orderAmount;
    }

    /**
     * @param float $orderAmount
     * @return DeliveryParams
     * @throws DeliveriesException
     */
    public function setOrderAmount(float $orderAmount): self
    {
        if (empty($orderAmount)) {
            throw new DeliveriesException('Error: orderAmount parameter is required.');
        }

        $this->orderAmount = $orderAmount;

        return $this;
    }


    public function getAddressCoordinates()
    {
        return $this->addressCoordinates;
    }


    public function setAddressCoordinates($addressCoordinates = ''): self
    {
        $this->addressCoordinates = $addressCoordinates;

        return $this;
    }


    public function getDesiredDate(): string
    {
        return $this->desiredDate;
    }


    public function setDesiredDate(string $desiredDate = ''): self
    {
        $this->desiredDate = $desiredDate;

        return $this;
    }


    public function getClientPostcode(): string
    {
        return $this->clientPostcode;
    }


    public function setClientPostcode(string $clientPostcode = ''): self
    {
        $this->clientPostcode = $clientPostcode;

        return $this;
    }
}