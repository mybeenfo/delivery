<?php

namespace Monastirevrf\DeliveryService\Service\Deliveries;

use Monastirevrf\DeliveryService\Exceptions\DeliveriesException;
use Monastirevrf\DeliveryService\Repository\DeliveryLocationRepository;
use Monastirevrf\DeliveryService\Repository\DeliveryOptionRepository;
use Monastirevrf\DeliveryService\Service\Deliveries\CourierServiceExpress\CourierServiceExpress;
use Monastirevrf\DeliveryService\Service\Deliveries\CourierServiceExpress\CourierServiceExpressApi;
use Monastirevrf\DeliveryService\Service\Deliveries\ExpressDelivery\ExpressDelivery;
use Symfony\Component\Cache\Adapter\AdapterInterface;

abstract class DeliveriesFactory
{
    const DELIVERIES_COURIER = [
        ExpressDelivery::DELIVERY_CODE => [
            'class' => ExpressDelivery::class,
            'deliveryApi' => null,
        ],

        CourierServiceExpress::DELIVERY_CODE => [
            'class' => CourierServiceExpress::class,
            'deliveryApi' => CourierServiceExpressApi::class,
        ],
    ];

    const DELIVERIES_PICKUP = [

    ];

    abstract function getCourier(
        string $courierType,
        DeliveryParams $deliveryParams,
        DeliveryLocationRepository $deliveryLocationRepository,
        ?ApiProvider $deliveryApi = null
    ): ?CourierInterface;

    abstract function getPickUpPoint(
        string $pickUpPointType,
        DeliveryParams $deliveryParams,
        DeliveryLocationRepository $deliveryLocationRepository
    ): ?PickUpPointInterface;

    public static function getFactory(string $deliveryType): DeliveriesFactory
    {
        return new $deliveryType();
    }

    public static function getDelivery(
        DeliveryParams $deliveryParams,
        DeliveryLocationRepository $deliveryLocationRepository,
        DeliveryOptionRepository $deliveryOptionRepository,
        AdapterInterface $cacheAdapter
    ) {
        $deliveryApi = null;

        if (!empty(self::DELIVERIES_COURIER[$deliveryParams->getDeliveryCode()]['class'])) {

            if (!empty(self::DELIVERIES_COURIER[$deliveryParams->getDeliveryCode()]['deliveryApi'])) {
                $deliveryApi = self::getApi
                (
                    self::DELIVERIES_COURIER[$deliveryParams->getDeliveryCode()]['deliveryApi'], $deliveryParams->getDeliveryCode(), $deliveryOptionRepository, $cacheAdapter
                );
            }

            $deliveryObj = self::getFactory(CourierFactory::class)
                ->getCourier(self::DELIVERIES_COURIER[$deliveryParams->getDeliveryCode()]['class'], $deliveryParams, $deliveryLocationRepository, $deliveryApi);

        } elseif (!empty(self::DELIVERIES_PICKUP[$deliveryParams->getDeliveryCode()]['class'])) {

            $deliveryObj = self::getFactory(PickUpPointFactory::class)
                ->getPickUpPoint(self::DELIVERIES_COURIER[$deliveryParams->getDeliveryCode()]['class'],
                    $deliveryParams,
                    $deliveryLocationRepository);

        } else {
            throw new DeliveriesException('Error: Factory not found');
        }

        return $deliveryObj;
    }

    public static function getApi(
        string $apiClass,
        string $deliveryCode,
        DeliveryOptionRepository $deliveryOptionRepository,
        AdapterInterface $cacheAdapter
    ): ApiProvider {
        return new $apiClass($deliveryCode, $deliveryOptionRepository, $cacheAdapter);
    }
}