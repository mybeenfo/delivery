<?php

namespace Monastirevrf\DeliveryService\Service\Deliveries;

use Monastirevrf\DeliveryService\Repository\DeliveryLocationRepository;

class CourierFactory extends DeliveriesFactory
{
    public function getCourier(
        string $courierType,
        DeliveryParams $deliveryParams,
        DeliveryLocationRepository $deliveryLocationRepository,
        ?ApiProvider $deliveryApi = null
    ): ?CourierInterface {
        if ($courierType == null) {
            return null;
        }

        return new $courierType($deliveryParams, $deliveryLocationRepository, $deliveryApi);
    }


    public function getPickUpPoint(
        string $pickUpPointType,
        DeliveryParams $deliveryParams,
        DeliveryLocationRepository $deliveryLocationRepository
    ): ?PickUpPointInterface {
        return null;
    }
}