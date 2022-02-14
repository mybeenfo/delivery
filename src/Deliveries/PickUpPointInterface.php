<?php

namespace Monastirevrf\DeliveryService\Service\Deliveries;

use Monastirevrf\DeliveryService\Repository\DeliveryLocationRepository;

interface PickUpPointInterface
{
    public function __construct(DeliveryParams $deliveryParams, DeliveryLocationRepository $deliveryLocationRepository);
}