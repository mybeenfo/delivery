<?php

namespace Monastirevrf\DeliveryService\Service\Deliveries;

interface CourierInterface
{
    const COEFFICIENT_DELIMITER = ';';

    function getDeliveryAvailable(): bool;

    function getDeliveryMinPrice(): int;

    function getDeliveryMinDate(): string;

    function getDeliveryPrice(): int;

    function deliveryDate(): string;

    function freeDeliverySumm(): ?int;

    function deliveryTradePointId(): int;
}