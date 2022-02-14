<?php

namespace Monastirevrf\DeliveryService\Service\Deliveries\CourierServiceExpress;

use DateTime;
use DateTimeZone;
use Exception;

use Psr\Cache\InvalidArgumentException;

use Monastirevrf\DeliveryService\Entity\DeliveryLocation;
use Monastirevrf\DeliveryService\Entity\DeliveryLocationTariff;
use Monastirevrf\DeliveryService\Exceptions\DeliveriesException;
use Monastirevrf\DeliveryService\Repository\DeliveryLocationRepository;
use Monastirevrf\DeliveryService\Service\Deliveries\ApiProvider;
use Monastirevrf\DeliveryService\Service\Deliveries\AbstractCourier;
use Monastirevrf\DeliveryService\Service\Deliveries\CourierInterface;
use Monastirevrf\DeliveryService\Service\Deliveries\DeliveryParams;

/**
 * Курьер Сервис Экспресс
 */
class CourierServiceExpress extends AbstractCourier implements CourierInterface
{
    const DELIVERY_CODE = 'courier_service_express';

    const COURIER_MIN_PERIOD = 1;

    /**
     * @var float
     */
    private $minDeliveryPrice;

    /**
     * @var DateTime
     */
    private $minDeliveryDate;

    /**
     * @throws DeliveriesException
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function __construct(
        DeliveryParams $deliveryParams,
        DeliveryLocationRepository $deliveryLocationRepository,
        ?ApiProvider $deliveryApi = null
    ) {
        $geoParams = $deliveryParams->getGeoParams();
        $orderAmount = $deliveryParams->getOrderAmount();
        $desiredDate = $deliveryParams->getDesiredDate();
        $deliveryCode = $deliveryParams->getDeliveryCode();
        $clientPostCode = $deliveryParams->getClientPostcode();
        $tradePointsFromRequest = $deliveryParams->getTradePointsFromRequest();
        $tradePointsIds = $this->getTradePointsIdsFromRequest($tradePointsFromRequest);

        parent::__construct($deliveryLocationRepository, $deliveryCode, $geoParams, $tradePointsIds, $orderAmount);

        $tradePointsReadyTimes = $this->getTradePointsReadyTimesFromRequest($tradePointsFromRequest);
        $this->setAvailableInTariff();
        $this->setTradePointsWithReadyTimeInTariffs($tradePointsReadyTimes);
        $this->setDeliveryDateInTariff();
        $this->available = $this->isAvailableTradePoints();
        $this->minDeliveryDate = $this->getMinDeliveryDateByLocationTariffs();
        $this->minDeliveryPrice = $this->getDeliveryMinPriceByLocationTariffs();

        $addressCoordinates = $deliveryParams->getAddressCoordinates();
        if (!empty($addressCoordinates) && is_array($addressCoordinates)) {
            $this->setDistanceFromClientToTradePointInTariffs($addressCoordinates);
            $this->setNearestTradePoint();

            $calculateResult = $deliveryApi->calculate($this->nearestTradePoint->getTradePointPostCode(), $clientPostCode);

            $this->setDeliveryPriceInNearestTradePoint($calculateResult['price']);
            $this->setDeliveryDateTimeInNearestTradePoint($calculateResult['minPeriod'], $desiredDate);
        }
    }

    function getDeliveryAvailable(): bool
    {
        return $this->available;
    }

    function getDeliveryMinPrice(): int
    {
        return $this->minDeliveryPrice;
    }

    function getDeliveryMinDate(): string
    {
        return $this->minDeliveryDate;
    }

    function getDeliveryPrice(): int
    {
        return $this->nearestTradePoint->getDeliveryPrice();
    }

    function deliveryDate(): string
    {
        return $this->nearestTradePoint->getDeliveryDateTime()->format(self::DATE_TIME_FORMAT);
    }

    function freeDeliverySumm(): ?int
    {
        return $this->nearestTradePoint->getLocation()->getFreeDeliveryFromSumm();
    }

    function deliveryTradePointId(): int
    {
        return $this->nearestTradePoint->getTradePointId();
    }


    /**
     * Установка доступности тарифов
     */
    private function setAvailableInTariff()
    {
        foreach ($this->locations as $location) {
            foreach ($location->getDeliveryLocationTariffs() as $tariff) {
                $tariff->setAvailable(true);
            }
        }
    }


    /**
     * Установка даты доставки в тарифы
     */
    private function setDeliveryDateInTariff(): void
    {
        $minDeliveryPeriod = $this->getCourierMinPeriod();

        foreach ($this->locations as $location) {
            foreach ($location->getDeliveryLocationTariffs() as $tariff) {
                try {
                    $deliveryDateTime = $this->calculationOfDeliveryDate($location, $tariff, $minDeliveryPeriod);
                    $tariff->setDeliveryDateTime($deliveryDateTime);
                } catch (DeliveriesException $e) {
                    continue;
                }

            }
        }
    }


    /**
     * Расчёт даты доставки
     */
    private function calculationOfDeliveryDate(
        DeliveryLocation $location,
        DeliveryLocationTariff $tariff,
        int $minDeliveryPeriod,
        string $desiredDate = ''
    ): DateTime {

        if (empty($tariff->getOrderPickupTime())) {
            $tariff->setAvailable(false);
            throw new DeliveriesException('Error: orderPickupTime is empty');
        }

        // Временная зона
        $timeZone = new DateTimeZone($location->getTimeZone());

        if (!empty($desiredDate)) {
            $desiredDate = new DateTime($desiredDate, $timeZone);
            $desiredDate->setTime(0, 0);
        }

        $orderPickupTime = $tariff->getOrderPickupTime();
        $orderPickupTime = (int)$orderPickupTime->format('H');

        // Текущая дата и время
        $currentDateTime = new DateTime(null, $timeZone);

        // Текущий час
        $currentHour = (int)$currentDateTime->format('H');

        // Если текущий час больше либо равен максимальному времени доставки то добавляем периоду ещё один день
        $period = $minDeliveryPeriod;
        if ($currentHour >= $orderPickupTime) {
            $period++;
        }

        // Дата доставки = текущая дата доставки + период
        $deliveryDateTime = $currentDateTime->modify('+' . $period . 'days');
        $deliveryDateTime->setTime(0, 0);

        // Дата и время готовности заказа из тарифа
        $tradePointsWithReadyDateTime = clone $tariff->getTradePointsWithReadyTime();
        // Час готовности заказа из тарифа
        $tradePointsWithReadyHour = (int)$tradePointsWithReadyDateTime->format('H');

        if (!empty($desiredDate) // Если есть желаемая дата доставки
            && $desiredDate >= $deliveryDateTime // И желаемая дата доставки больше, либо равна рассчитанной дате
            && $desiredDate > $tradePointsWithReadyDateTime // И желаемая дата доставки больше даты готовности заказа в торговой точке
        ) {
            $deliveryDateTime = $desiredDate;
        } elseif ($tradePointsWithReadyDateTime >= $deliveryDateTime  // Если дата готовности заказа больше либо равна рассчитанной дате доставки
            && $tradePointsWithReadyHour >= $orderPickupTime) // И время готовности заказа больше, либо равно времени забора заказа курьером
        {
            $deliveryDateTime = $tradePointsWithReadyDateTime->modify('+' . $minDeliveryPeriod . 'days');
        } elseif ($tradePointsWithReadyDateTime >= $deliveryDateTime  // Если дата готовности заказа больше либо равна рассчитанной дате доставки
            && $tradePointsWithReadyHour < $orderPickupTime) // И время готовности заказа меньше времени забора заказа курьером
        {
            $deliveryDateTime = $tradePointsWithReadyDateTime;
        }

        return $deliveryDateTime;
    }


    /**
     * Установка цены доставки в ближайшую точку тарифа
     */
    private function setDeliveryPriceInNearestTradePoint(int $deliveryPrice): void
    {
        if ($this->isFreeDelivery() === true) {
            $this->nearestTradePoint->setDeliveryPrice(0);

            return;
        }

        if (empty($deliveryPrice)) {
            throw new DeliveriesException('CSE Error: price is empty');
        }

        $this->nearestTradePoint->setDeliveryPrice($deliveryPrice);
    }


    /**
     * Установка даты доставки в ближайшую точку тарифа
     *
     * @throws DeliveriesException
     */
    private function setDeliveryDateTimeInNearestTradePoint(int $minDeliveryPeriod, string $desiredDate): void
    {
        if (empty($minDeliveryPeriod)) {
            throw new DeliveriesException('CSE Error: minPeriod is empty');
        }

        if (empty($desiredDate)) {
            throw new DeliveriesException('CSE Error: desiredDate parameter is empty');
        }

        $deliveryDateTime = $this->calculationOfDeliveryDate($this->nearestTradePoint->getLocation(), $this->nearestTradePoint, $minDeliveryPeriod, $desiredDate);
        $this->nearestTradePoint->setDeliveryDateTime($deliveryDateTime);
    }


    /**
     * Получение минимального промежутка доставки
     */
    private function getCourierMinPeriod(): int
    {
        return self::COURIER_MIN_PERIOD;
    }

}