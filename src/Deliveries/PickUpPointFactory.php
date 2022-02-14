<?php

namespace Monastirevrf\DeliveryService\Service\Deliveries;

use Monastirevrf\DeliveryService\Repository\DeliveryLocationRepository;

class PickUpPointFactory extends DeliveriesFactory
{
    public function getPickUpPoint(
        string $pickUpPointType,
        DeliveryParams $deliveryParams,
        DeliveryLocationRepository $deliveryLocationRepository
    ): ?PickUpPointInterface {
        if ($pickUpPointType == null) {
            return null;
        }

        return new $pickUpPointType();
    }

    public function getCourier(
        string $courierType,
        DeliveryParams $deliveryParams,
        DeliveryLocationRepository $deliveryLocationRepository,
        ?ApiProvider $deliveryApi = null
    ): ?CourierInterface {
        return null;
    }
}