<?php

namespace Monastirevrf\DeliveryService\Service\Deliveries;

use Monastirevrf\DeliveryService\Repository\DeliveryOptionRepository;
use Symfony\Component\Cache\Adapter\AdapterInterface;

interface ApiProvider
{
    public function __construct(string $deliveryCode, DeliveryOptionRepository $deliveryOptionRepository, AdapterInterface $cacheAdapter);
}