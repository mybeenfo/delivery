<?php

namespace Monastirevrf\DeliveryService\Service\Deliveries;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use Monastirevrf\DeliveryService\Entity\DeliveryLocationTariff;
use Monastirevrf\DeliveryService\Exceptions\DeliveriesException;
use Monastirevrf\DeliveryService\Repository\DeliveryLocationRepository;

/**
 * Абстрактный класс курьеров
 */
abstract class AbstractCourier
{
    const DELIVERY_CODE = 'express_delivery';
    const DATE_TIME_FORMAT = DateTimeInterface::ATOM;

    const EARTH_RADIUS = 6372795;

    /**
     *
     * @var array
     */
    protected $locations;

    /**
     * @var DeliveryLocationTariff
     */
    protected $nearestTradePoint;

    /**
     * @var bool
     */
    protected $isFreeDelivery = false;


    /**
     * @var bool
     */
    protected $available;


    public function __construct(
        $deliveryLocationRepository,
        $deliveryCode,
        $geoParams,
        $tradePointsIds,
        $orderAmount
    ) {
        $this->locations = $this->findActiveDeliveriesInLocations($deliveryLocationRepository, $deliveryCode, $geoParams, $tradePointsIds);
        if (empty($this->locations)) {
            throw new DeliveriesException('There are no delivery services available.');
        }

        $this->setIsFreeDelivery($orderAmount);
    }

    /**
     * Проверка доступных торговых точек
     *
     * @throws DeliveriesException
     */
    protected function isAvailableTradePoints(): bool
    {
        $availablePoints = [];

        foreach ($this->locations as $location) {
            foreach ($location->getDeliveryLocationTariffs() as $tariff) {
                if ($tariff->getAvailable() === true) {
                    $availablePoints[] = $tariff->getTradePointId();
                }
            }
        }

        if (empty($availablePoints)) {
            throw new DeliveriesException('No available retail trade points were found.');
        }

        return !empty($availablePoints);
    }


    /**
     * Устанавливает является ли доставка бесплатной
     */
    protected function setIsFreeDelivery(float $orderAmount)
    {
        foreach ($this->locations as $location) {
            $freeDeliveryFromSumm = $location->getFreeDeliveryFromSumm();

            if (!empty($freeDeliveryFromSumm) && !empty($orderAmount) && $orderAmount >= $freeDeliveryFromSumm) {
                $this->isFreeDelivery = true;
                break;
            }
        }
    }


    /**
     * Возвращает является ли доставка бесплатной
     */
    protected function isFreeDelivery(): bool
    {
        return $this->isFreeDelivery;
    }


    /**
     * Формирование массива id торговых точек из запроса
     *
     * @throws DeliveriesException|Exception
     */
    protected function getTradePointsIdsFromRequest(array $tradePointsFromRequest): array
    {
        $tradePointsIds = [];

        foreach ($tradePointsFromRequest as $tradePoint) {

            if (empty($tradePoint['id'])) {
                throw new Exception('Error: tradePointsWithReadyTimes array parameter must contain the id field.', 401);
            }
            $tradePointsIds[] = $tradePoint['id'];
        }

        return $tradePointsIds;
    }


    /**
     * Формирование массива дат готовности заказов в торговых точках из запроса
     *
     * @throws DeliveriesException
     */
    protected function getTradePointsReadyTimesFromRequest(array $tradePointsFromRequest): array
    {
        $tradePointsReadyTimes = [];

        foreach ($tradePointsFromRequest as $tradePoint) {
            if (empty($tradePoint['id'] || empty($tradePoint['readyTime']))) {
                throw new DeliveriesException('Error: tradePoints array parameter must contain id and readyTime fields.');
            }

            $tradePointsReadyTimes[$tradePoint['id']] = $tradePoint['readyTime'];
        }

        return $tradePointsReadyTimes;
    }

    protected function findActiveDeliveriesInLocations(DeliveryLocationRepository $deliveryLocationRepository, $deliveryCode, $geoParams, $tradePointsIds): array
    {
        return $deliveryLocationRepository->findActiveDeliveries($deliveryCode, $geoParams, $tradePointsIds);
    }


    /**
     * Получение минимальной цены доставки из тарифов местоположений
     */
    protected function getDeliveryMinPriceByLocationTariffs(): int
    {
        $minDeliveryPrice = null;

        if ($this->isFreeDelivery() === true) {
            return 0;
        }

        foreach ($this->locations as $location) {
            foreach ($location->getDeliveryLocationTariffs() as $tariff) {
                $deliveryPrice = $tariff->getMinDeliveryPrice();

                if (empty($deliveryPrice) || $tariff->getAvailable() !== true) {
                    continue;
                }

                if ($minDeliveryPrice == null || $deliveryPrice < $minDeliveryPrice) {
                    $minDeliveryPrice = $deliveryPrice;
                }
            }
        }

        return $minDeliveryPrice;
    }


    /**
     * Запись даты и время готовности заказа в торговой точке, в тарифы местоположений
     *
     * @throws Exception
     */
    protected function setTradePointsWithReadyTimeInTariffs(array $tradePointsReadyTimes): void
    {
        foreach ($this->locations as $location) {
            foreach ($location->getDeliveryLocationTariffs() as $tariff) {
                if (!empty($tradePointsReadyTimes[$tariff->getTradePointId()])) {
                    $deliveryDateTime = new DateTime($tradePointsReadyTimes[$tariff->getTradePointId()], new DateTimeZone($location->getTimeZone()));
                    $tariff->setTradePointsWithReadyTime($deliveryDateTime);
                }
            }
        }
    }


    /**
     * Получение минимальной даты доставки и тарифов
     */
    protected function getMinDeliveryDateByLocationTariffs(): string
    {
        $minDeliveryDate = null;

        foreach ($this->locations as $location) {
            foreach ($location->getDeliveryLocationTariffs() as $tariff) {
                $deliveryDate = $tariff->getDeliveryDateTime();

                if (empty($deliveryDate) || $tariff->getAvailable() !== true) {
                    continue;
                }

                if ($minDeliveryDate == null || $deliveryDate < $minDeliveryDate) {
                    $minDeliveryDate = $deliveryDate;
                }
            }
        }

        return $minDeliveryDate->format(self::DATE_TIME_FORMAT);
    }


    /**
     * Установка расстояния от клиента до торговой точки в тарифах
     *
     * @throws DeliveriesException
     */
    protected function setDistanceFromClientToTradePointInTariffs($clientLocation): void
    {
        $clientLocationLat = $clientLocation['lat'];
        $clientLocationLon = $clientLocation['lon'];

        if (empty($clientLocationLat)) {
            throw new DeliveriesException('Error: addressCoordinates[lat] parameter is required.');
        }

        if (empty($clientLocationLon)) {
            throw new DeliveriesException('Error: addressCoordinates[lon] parameter is required.');
        }

        foreach ($this->locations as $location) {
            foreach ($location->getDeliveryLocationTariffs() as $tariff) {

                $tariffTradePointLatitude = $tariff->getTradePointLatitude();
                $tariffTradePointLongitude = $tariff->getTradePointLongitude();

                if (!empty($tariffTradePointLatitude) && !empty($tariffTradePointLongitude)) {

                    $distance = $this->calculateDistance($tariffTradePointLatitude, $tariffTradePointLongitude, $clientLocationLat, $clientLocationLon, false);
                    
                    $distance = ($distance / 1000);

                    if (empty($distance)) {
                        $distance = 1;
                    }

                    $tariff->setDeliveryDistance($distance);
                }
            }
        }
    }


    /*
    * Расчёт расстояние между двумя точками
    *
    * $latA, $longA - широта, долгота 1-й точки,
    * $latB, $longB - широта, долгота 2-й точки
    */
    function calculateDistance($latA, $longA, $latB, $longB, $km = true): float
    {

        // перевести координаты в радианы
        $lat1 = $latA * M_PI / 180;
        $lat2 = $latB * M_PI / 180;
        $long1 = $longA * M_PI / 180;
        $long2 = $longB * M_PI / 180;

        // косинусы и синусы широт и разницы долгот
        $cl1 = cos($lat1);
        $cl2 = cos($lat2);
        $sl1 = sin($lat1);
        $sl2 = sin($lat2);
        $delta = $long2 - $long1;
        $cDelta = cos($delta);
        $sDelta = sin($delta);

        // вычисления длины большого круга
        $y = sqrt(pow($cl2 * $sDelta, 2) + pow($cl1 * $sl2 - $sl1 * $cl2 * $cDelta, 2));
        $x = $sl1 * $sl2 + $cl1 * $cl2 * $cDelta;

        $ad = atan2($y, $x);

        $result = $ad * self::EARTH_RADIUS;
        $result = round($result);

        if ($km === true) {
            $result = $result / 1000;
        }

        return $result;
    }


    /**
     * Устанавливает стоимости доставки по радиусу в тариф
     *
     * @throws DeliveriesException
     */
    protected function setCostOfDeliveryByRadiusInTariffs()
    {
        foreach ($this->locations as $location) {
            foreach ($location->getDeliveryLocationTariffs() as $tariff) {

                $costOfDeliveryByRadius = 0;

                if ($tariff->getCalculationByRadius() === true) {

                    $distance = $tariff->getDeliveryDistance() * 1000;

                    $radiusRules = [];

                    if (empty($tariff->getCostRulesDeliveryByRadius())) {
                        throw new DeliveriesException('Error: In the tariff ID ' . $tariff->getId() . ' field cost_rules_delivery_by_radius is empty');
                    }

                    foreach ($tariff->getCostRulesDeliveryByRadius() as $rule) {
                        $radiusRules[] = explode(CourierInterface::COEFFICIENT_DELIMITER, $rule);
                    }

                    if (is_array($radiusRules) && !empty($radiusRules)) {

                        end($radiusRules);
                        $lastRadiusRuleKey = key($radiusRules);

                        for ($i = 0; $i < count($radiusRules); $i++) {
                            $radiusRuleParams = $radiusRules[$i];
                            $radiusRuleDistance = (int)$radiusRuleParams[0];
                            $radiusRuleParam = (float)$radiusRuleParams[1];

                            if ($i == 0 && $distance < $radiusRuleDistance) {
                                $costOfDeliveryByRadius = $radiusRuleParam;
                                break;
                            } elseif ($i != $lastRadiusRuleKey) {
                                $nextRadiusRule = $radiusRules[$i + 1];
                                $nextRadiusRuleDistance = (int)$nextRadiusRule[0];
                                $nextRadiusRule = (float)$nextRadiusRule[1];

                                if ($distance > $radiusRuleDistance && $distance < $nextRadiusRuleDistance) {
                                    $costOfDeliveryByRadius = $nextRadiusRule;
                                    break;
                                }

                            } else {
                                $tariff->setAvailable(false);
                            }
                        }
                    }
                }

                $tariff->setCostOfDeliveryByRadius($costOfDeliveryByRadius);
            }

        }
    }


    /**
     * Установка ближайшей и доступной точки доставки по расстоянию
     */
    protected function setNearestTradePoint(): void
    {
        $minDeliveryDistance = null;

        foreach ($this->locations as $location) {
            foreach ($location->getDeliveryLocationTariffs() as $tariff) {
                $deliveryDistance = $tariff->getDeliveryDistance();

                if (!empty($deliveryDistance) && $tariff->getAvailable() === true) {
                    if ($minDeliveryDistance == null) {
                        $minDeliveryDistance = $deliveryDistance;
                        $this->nearestTradePoint = $tariff;
                    } else {
                        if ($deliveryDistance < $minDeliveryDistance) {
                            $this->nearestTradePoint = $tariff;
                        }
                    }
                }
            }
        }

    }
}