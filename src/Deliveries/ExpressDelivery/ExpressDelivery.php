<?php

namespace Monastirevrf\DeliveryService\Service\Deliveries\ExpressDelivery;

use DateTimeZone;
use Exception;

use Monastirevrf\DeliveryService\Entity\DeliveryLocation;
use Monastirevrf\DeliveryService\Entity\DeliveryLocationTariff;

use Monastirevrf\DeliveryService\Helpers\DateTimeHelper;
use Monastirevrf\DeliveryService\Service\Deliveries\ApiProvider;
use Monastirevrf\DeliveryService\Service\Deliveries\AbstractCourier;
use Monastirevrf\DeliveryService\Service\Deliveries\CourierInterface;
use Monastirevrf\DeliveryService\Service\Deliveries\DeliveryParams;

use Monastirevrf\DeliveryService\Repository\DeliveryLocationRepository;
use Monastirevrf\DeliveryService\Exceptions\DeliveriesException;

/**
 * Экспресс доставка
 */
class ExpressDelivery extends AbstractCourier implements CourierInterface
{
    /**
     * @var float
     */
    private $minDeliveryPrice;

    /**
     * @var float
     */
    private $minDeliveryDate;

    /**
     * Инициализация службы доставки
     * Получение необходимых данных и запись в свойства.
     *
     * @throws DeliveriesException
     * @throws Exception
     */
    public function __construct(
        DeliveryParams $deliveryParams,
        DeliveryLocationRepository $deliveryLocationRepository,
        ?ApiProvider $deliveryApi = null
    ) {
        $deliveryCode = $deliveryParams->getDeliveryCode();
        $geoParams = $deliveryParams->getGeoParams();
        $tradePointsFromRequest = $deliveryParams->getTradePointsFromRequest();
        $orderAmount = $deliveryParams->getOrderAmount();

        $tradePointsIds = $this->getTradePointsIdsFromRequest($tradePointsFromRequest);
        parent::__construct($deliveryLocationRepository, $deliveryCode, $geoParams, $tradePointsIds, $orderAmount);

        $tradePointsReadyTimes = $this->getTradePointsReadyTimesFromRequest($tradePointsFromRequest);
        $this->setTradePointsWithReadyTimeInTariffs($tradePointsReadyTimes);
        $this->setDeliveryDateTimeInTariff();
        $this->setAvailableInTariff();
        $this->available = $this->isAvailableTradePoints();
        $this->minDeliveryDate = $this->getMinDeliveryDateByLocationTariffs();
        $this->minDeliveryPrice = $this->getDeliveryMinPriceByLocationTariffs();

        $addressCoordinates = $deliveryParams->getAddressCoordinates();
        if (!empty($addressCoordinates) && is_array($addressCoordinates)) {
            $this->setDistanceFromClientToTradePointInTariffs($addressCoordinates);
            $this->setCostOfDeliveryByRadiusInTariffs();
            $this->setDeliveryPriceInTariffs();

            $this->setNearestTradePoint();
            
           if (empty($this->nearestTradePoint)) {
               throw new DeliveriesException('No available retail trade points were found.', Response::HTTP_OK);
           }
        }
    }

    public function getDeliveryAvailable(): bool
    {
        return $this->available;
    }

    public function getDeliveryMinPrice(): int
    {
        return $this->minDeliveryPrice;
    }

    public function getDeliveryMinDate(): string
    {
        return $this->minDeliveryDate;
    }

    public function getDeliveryPrice(): int
    {
        return $this->nearestTradePoint->getDeliveryPrice();
    }

    public function deliveryDate(): string
    {
        return $this->nearestTradePoint->getDeliveryDateTime()->format(self::DATE_TIME_FORMAT);
    }

    public function freeDeliverySumm(): ?int
    {
        return $this->nearestTradePoint->getLocation()->getFreeDeliveryFromSumm();
    }

    public function deliveryTradePointId(): int
    {
        return $this->nearestTradePoint->getTradePointId();
    }


    /**
     * Расчёт и запись даты доставки в тарифы
     *
     * @throws Exception
     */
    private function setDeliveryDateTimeInTariff(): void
    {
        foreach ($this->locations as $location) {
            foreach ($location->getDeliveryLocationTariffs() as $tariff) {

                $tariffDeliveryTime = $tariff->getDeliveryTime();
                if (empty($tariffDeliveryTime)) {
                    throw new DeliveriesException('Error: In the tariff ID ' . $tariff->getId() . ' field tariff_delivery_time is empty');
                }

                if (!empty($tariff->getTradePointsWithReadyTime())) {
                    $deliveryDateTime = clone $tariff->getTradePointsWithReadyTime();
                    $deliveryDateTime->modify('+' . $tariffDeliveryTime . 'hours');
                    $tariff->setDeliveryDateTime($deliveryDateTime);
                }
            }
        }
    }


    /**
     * Расчёт и запись доступности доставки в тарифы
     */
    private function setAvailableInTariff(): void
    {
        foreach ($this->locations as $location) {
            foreach ($location->getDeliveryLocationTariffs() as $tariff) {
                $availableTradePoint = $this->isAvailableTradePoint($location, $tariff);

                if ($availableTradePoint === true) {
                    $tariff->setAvailable(true);
                }
            }
        }
    }


    /**
     * Определение доступна ли торговая точка для доставки
     */
    private function isAvailableTradePoint(DeliveryLocation $location, DeliveryLocationTariff $tariff): bool
    {
        $availableTradePoint = false;
        $availabilityPointByDeliveryTime = $this->calculatingAvailabilityPointByDeliveryTime($location, $tariff);

        if ($availabilityPointByDeliveryTime === true) {
            $availableTradePoint = true;
        }

        return $availableTradePoint;
    }


    /**
     * Расчёт доступности торговой точки по времени доставки
     *  ..Время готовности заказа попадает в рабочее время курьера
     *  ..Время готовности заказа попадает в рабочее время торговой точки
     *  ..Время доставки заказа попадает в рабочее время курьера
     */
    private function calculatingAvailabilityPointByDeliveryTime(DeliveryLocation $location, DeliveryLocationTariff $tariff): bool
    {
        $result = false;

        if (empty($tariff->getTradePointsWithReadyTime()) && empty($tariff->getDeliveryDateTime())) {
            return $result;
        }

        // Временная зона местоположения
        $timeZone = new DateTimeZone($location->getTimeZone());

        // Время готовности заказа
        $tradePointsWithReadyTime = DateTimeHelper::getUnixTime($tariff->getTradePointsWithReadyTime(), $timeZone);
        // Время во сколько будет доставка клиенту
        $deliveryTimeToClient = DateTimeHelper::getUnixTime($tariff->getDeliveryDateTime(), $timeZone);
        // Время работы курьера доставки от
        $courierWorkingTimeFrom = DateTimeHelper::getUnixTime($location->getDeliveryTimeFrom(), $timeZone);
        // Время работы курьера доставки до
        $courierWorkingTimeTo = DateTimeHelper::getUnixTime($location->getDeliveryTimeTo(), $timeZone);
        // Время работы торговой точки от
        $tradePointWorkingTimeFrom = DateTimeHelper::getUnixTime($tariff->getWorkOfTradePointWith(), $timeZone);
        // Время работы торговой точки до
        $tradePointWorkingTimeTo = DateTimeHelper::getUnixTime($tariff->getWorkOfTradePointOn(), $timeZone);
        if (
            // Время готовности заказа попадает в рабочее время торговой точки
            $tradePointWorkingTimeFrom < $tradePointsWithReadyTime && $tradePointsWithReadyTime < $tradePointWorkingTimeTo &&
            // Время готовности заказа попадает в рабочее время курьера
            $courierWorkingTimeFrom < $tradePointsWithReadyTime && $tradePointsWithReadyTime < $courierWorkingTimeTo &&
            // Время доставки заказа попадает в рабочее время курьера
            $courierWorkingTimeFrom < $deliveryTimeToClient && $deliveryTimeToClient < $courierWorkingTimeTo
        ) {
            $result = true;
        }

        return $result;
    }


    /**
     * Установка цены в тарифах
     */
    private function setDeliveryPriceInTariffs(): void
    {
        foreach ($this->locations as $location) {
            foreach ($location->getDeliveryLocationTariffs() as $tariff) {

                $pricePerKilometer = $tariff->getPricePerKilometer();

                if (empty($pricePerKilometer)) {
                    throw new DeliveriesException('Error: In the tariff ID ' . $tariff->getId() . ' field price_per_kilometer is empty');
                }

                if ($this->isFreeDelivery() === true) {
                    $tariff->setDeliveryPrice(0);
                    continue;
                }

                $distance = $tariff->getDeliveryDistance();
                $costOfDeliveryByRadius = $tariff->getCostOfDeliveryByRadius();

                if (empty($distance)) {
                    return;
                }

                if ($costOfDeliveryByRadius > 0) {
                    $tariff->setDeliveryPrice($costOfDeliveryByRadius);
                    return;
                }

                $deliverySumm = $distance * $pricePerKilometer;
                $tariff->setDeliveryPrice($deliverySumm);
            }
        }
    }

}