<?php

namespace Monastirevrf\DeliveryService\Service\Deliveries\CourierServiceExpress;

use SoapClient;
use SoapFault;
use Psr\Cache\InvalidArgumentException;

use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\RedisAdapter;

use Monastirevrf\DeliveryService\Exceptions\DeliveriesException;
use Monastirevrf\DeliveryService\Repository\DeliveryOptionRepository;
use Monastirevrf\DeliveryService\Service\Deliveries\ApiProvider;

/**
 * API Курьер Сервис Экспресс
 * API documentation https://lk.cse.ru/files/uploads/extended_api_2021_04_05.pdf
 */
class CourierServiceExpressApi implements ApiProvider
{
    use QueryStructures;

    private const OPTION_API_URL = 'CSE_API_URL';
    private const OPTION_API_LOGIN = 'CSE_API_LOGIN';
    private const OPTION_API_PASSWORD = 'CSE_API_PASSWORD';
    private const OPTION_CARGO_TYPE = 'CSE_CARGO_TYPE';
    private const OPTION_SERVICE_TYPE = 'CSE_SERVICE_TYPE';
    private const OPTION_URGENCY_TYPE = 'CSE_URGENCY_TYPE';
    private const OPTION_DELIVERY_TYPE = 'CSE_DELIVERY_TYPE';

    private const REQUIRE_OPTIONS = [
        self::OPTION_API_URL,
        self::OPTION_API_LOGIN,
        self::OPTION_API_PASSWORD,
        self::OPTION_CARGO_TYPE,
        self::OPTION_SERVICE_TYPE,
        self::OPTION_URGENCY_TYPE,
        self::OPTION_DELIVERY_TYPE,
    ];

    private const CACHE_NAME_SPACE = CourierServiceExpress::DELIVERY_CODE . '_api';
    private const CACHE_TTL = 86400;

    /**
     * @var DeliveryOptionRepository
     */
    private $deliveryOptionRepository;

    /**
     * @var array
     */
    private $options;

    /**
     * @var SoapClient
     */
    private $soapClient;

    /**
     * @var RedisAdapter
     */
    private $cachePool;

    /**
     * @throws DeliveriesException
     * @throws SoapFault
     */
    public function __construct(string $deliveryCode, DeliveryOptionRepository $deliveryOptionRepository, AdapterInterface $cacheAdapter)
    {
        $this->deliveryOptionRepository = $deliveryOptionRepository;
        $this->options = $this->getOptions($deliveryCode);
        $this->soapClient = new SoapClient($this->options[self::OPTION_API_URL]);

        $this->cachePool = $cacheAdapter;
    }


    /**
     * Получение параметров доставки от сервиса Курьер сервис экспресс
     *
     * @throws DeliveriesException
     * @throws InvalidArgumentException
     */
    public function calculate(string $tradePointPostcode, string $clientPostcode, float $weight = 1, int $packagesCount = 1, $volume = 1): array
    {
        if (empty($tradePointPostcode)) {
            throw new DeliveriesException('The parameter desiredDate must be passed in the request parameters');
        }

        if (empty($clientPostcode)) {
            throw new DeliveriesException('The parameter postcode must be passed in the request parameters');
        }

        $cacheCalculate = $this->cachePool->getItem($tradePointPostcode . '_' . $clientPostcode . '_' . $weight . '_' . $packagesCount . '_' . $volume);
        $cacheCalculate->expiresAfter(self::CACHE_TTL);
        if ($cacheCalculate->isHit() === true) {
            return $cacheCalculate->get();
        }

        $tradePointGeographyGuid = $this->getGeographyGuidByPostcode($tradePointPostcode);
        $clientGeographyGuid = $this->getGeographyGuidByPostcode($clientPostcode);
        $typeOfCargoGuid = $this->getTypeOfCargoGuid();
        $serviceGuid = $this->getServiceGuid();
        $urgencyGuid = $this->getUrgencyGuid();
        $deliveryTypeValue = $this->getDeliveryTypeValue();

        $structureParams = [
            'login' => $this->options[self::OPTION_API_LOGIN],
            'password' => $this->options[self::OPTION_API_PASSWORD],
            'tradePointGeographyGuid' => $tradePointGeographyGuid,
            'clientGeographyGuid' => $clientGeographyGuid,
            'typeOfCargoGuid' => $typeOfCargoGuid,
            'weight' => $weight,
            'packagesCount' => $packagesCount,
            'volume' => $volume,
            'serviceGuid' => $serviceGuid,
            'urgencyGuid' => $urgencyGuid,
            'deliveryTypeValue' => $deliveryTypeValue,
        ];

        $params = self::calcStructure($structureParams);
        $result = $this->soapClient->Calc($params);

        if (empty($result->return->List->List->Fields)) {
            throw new DeliveriesException("CSE Api Error: service sent empty calculation response");
        }

        $arResult = [];
        foreach ($result->return->List->List->Fields as $item) {
            $arResult[$item->Key] = $item->Value;
        }

        $arResult = [
            'price' => (float)$arResult['Total'],
            'minPeriod' => (int)$arResult['MinPeriod'],
            'maxPeriod' => (int)$arResult['MaxPeriod'],
        ];

        $cacheCalculate->set($arResult);
        $this->cachePool->save($cacheCalculate);

        return $cacheCalculate->get();
    }


    /**
     * Получение GUID (Globally Unique Identifier) географии отправления по почтовому индексу
     *
     * @throws DeliveriesException
     * @throws InvalidArgumentException
     */
    private function getGeographyGuidByPostcode(string $postcode): string
    {
        $cacheGeographyGuid = $this->cachePool->getItem($postcode);
        $cacheGeographyGuid->expiresAfter(self::CACHE_TTL);
        if ($cacheGeographyGuid->isHit() === true) {
            return $cacheGeographyGuid->get();
        }

        $params = self::guidStructure($this->options[self::OPTION_API_LOGIN], $this->options[self::OPTION_API_PASSWORD], $postcode);
        $result = $this->soapClient->GetReferenceData($params);

        if (empty($result->return->List->Key)) {
            throw new DeliveriesException("CSE Api Error: Don't find the geography GUID by postcode");
        }

        $cacheGeographyGuid->set($result->return->List->Key);
        $this->cachePool->save($cacheGeographyGuid);

        return $cacheGeographyGuid->get();
    }


    /**
     * Получение GUID (Globally Unique Identifier) вида груза соответствующего настройке в таблице delivery_options
     *
     * @throws DeliveriesException
     * @throws InvalidArgumentException
     */
    private function getTypeOfCargoGuid(): string
    {
        $cacheTypeOfCargoGuid = $this->cachePool->getItem(self::OPTION_CARGO_TYPE);
        $cacheTypeOfCargoGuid->expiresAfter(self::CACHE_TTL);
        if ($cacheTypeOfCargoGuid->isHit() === true) {
            return $cacheTypeOfCargoGuid->get();
        }

        $params = self::typeOfCargo($this->options[self::OPTION_API_LOGIN], $this->options[self::OPTION_API_PASSWORD]);
        $result = $this->soapClient->GetReferenceData($params);

        if (empty($result->return->List)) {
            throw new DeliveriesException("CSE Api Error: Don't find the type of cargo");
        }

        $typeOfCargoGuid = '';

        if (is_array($result->return->List)) {
            foreach ($result->return->List as $cargo) {
                if (!empty($cargo->Value) && $this->options[self::OPTION_CARGO_TYPE] == $cargo->Value) {
                    if (!empty($cargo->Key)) {
                        $typeOfCargoGuid = $cargo->Key;
                        break;
                    }

                }
            }
        } else {
            $typeOfCargoGuid = $result->return->List->Key;
        }

        if (empty($typeOfCargoGuid)) {
            throw new DeliveriesException("CSE Api Error: CargoGuid is empty");
        }

        $cacheTypeOfCargoGuid->set($typeOfCargoGuid);
        $this->cachePool->save($cacheTypeOfCargoGuid);

        return $cacheTypeOfCargoGuid->get();
    }


    /**
     * Получение GUID (Globally Unique Identifier) услуги соответствующей настройке в таблице delivery_options
     *
     * @throws DeliveriesException
     * @throws InvalidArgumentException
     */
    private function getServiceGuid(): string
    {
        $cacheServiceGuid = $this->cachePool->getItem(self::OPTION_SERVICE_TYPE);
        $cacheServiceGuid->expiresAfter(self::CACHE_TTL);
        if ($cacheServiceGuid->isHit() === true) {
            return $cacheServiceGuid->get();
        }

        $params = self::services($this->options[self::OPTION_API_LOGIN], $this->options[self::OPTION_API_PASSWORD]);
        $result = $this->soapClient->GetReferenceData($params);

        if (empty($result->return->List)) {
            throw new DeliveriesException("CSE Api Error: Don't find the services kes");
        }

        $serviceGuid = '';

        if (is_array($result->return->List)) {
            foreach ($result->return->List as $service) {
                if (!empty($service->Value) && $this->options[self::OPTION_SERVICE_TYPE] == $service->Value) {
                    if (!empty($service->Key)) {
                        $serviceGuid = $service->Key;
                        break;
                    }

                }
            }
        } else {
            $serviceGuid = $result->return->List->Key;
        }

        if (empty($serviceGuid)) {
            throw new DeliveriesException("CSE Api Error: serviceGuid is empty");
        }

        $cacheServiceGuid->set($serviceGuid);
        $this->cachePool->save($cacheServiceGuid);

        return $cacheServiceGuid->get();
    }


    /**
     * Получение GUID (Globally Unique Identifier) срочности доставки соответствующей настройке в таблице delivery_options
     *
     * @throws DeliveriesException
     * @throws InvalidArgumentException
     */
    private function getUrgencyGuid(): string
    {
        $cacheUrgencyGuid = $this->cachePool->getItem(self::OPTION_URGENCY_TYPE);
        $cacheUrgencyGuid->expiresAfter(self::CACHE_TTL);
        if ($cacheUrgencyGuid->isHit() === true) {
            return $cacheUrgencyGuid->get();
        }

        $params = self::urgency($this->options[self::OPTION_API_LOGIN], $this->options[self::OPTION_API_PASSWORD]);

        $result = $this->soapClient->GetReferenceData($params);

        if (empty($result->return->List)) {
            throw new DeliveriesException("CSE Api Error: Don't find the urgencies kes");
        }

        $urgencyGuid = '';

        if (is_array($result->return->List)) {
            foreach ($result->return->List as $urgency) {
                if (!empty($urgency->Value) && $urgency->Value == $this->options[self::OPTION_URGENCY_TYPE]) {
                    if (!empty($urgency->Key)) {
                        $urgencyGuid = $urgency->Key;
                        break;
                    }
                }
            }
        } else {
            if (!empty($result->return->List->Key)) {
                $urgencyGuid = $result->return->List->Key;
            }
        }

        if (empty($urgencyGuid)) {
            throw new DeliveriesException("CSE Api Error: Don't find the urgencies kes");
        }

        $cacheUrgencyGuid->set($urgencyGuid);
        $this->cachePool->save($cacheUrgencyGuid);

        return $cacheUrgencyGuid->get();
    }


    /**
     * Получение типа доставки грузов соответствующей настройке в таблице delivery_options
     *
     * @throws DeliveriesException
     * @throws InvalidArgumentException
     */
    private function getDeliveryTypeValue(): string
    {
        $cacheDeliveryTypeValue = $this->cachePool->getItem(self::OPTION_DELIVERY_TYPE);
        $cacheDeliveryTypeValue->expiresAfter(self::CACHE_TTL);
        if ($cacheDeliveryTypeValue->isHit() === true) {
            return $cacheDeliveryTypeValue->get();
        }

        $params = self::deliveryType($this->options[self::OPTION_API_LOGIN], $this->options[self::OPTION_API_PASSWORD]);
        $result = $this->soapClient->GetReferenceData($params);

        if (empty($result->return->List)) {
            throw new DeliveriesException("CSE Api Error: DeliveryType is empty");
        }

        $deliveryTypeValue = '';

        foreach ($result->return->List as $deliveryType) {
            foreach ($deliveryType->Fields as $field) {
                if ($field->Value == $this->options[self::OPTION_DELIVERY_TYPE]) {
                    $deliveryTypeValue = $deliveryType->Value;
                    break;
                }
            }
        }

        $cacheDeliveryTypeValue->set($deliveryTypeValue);
        $this->cachePool->save($cacheDeliveryTypeValue);

        return $deliveryTypeValue;
    }


    /**
     *  Получение настроек доставки из таблицы delivery_options
     *
     * @throws DeliveriesException
     */
    private function getOptions(string $deliveryCode): array
    {
        $options = [];
        $baseOptions = $this->deliveryOptionRepository->getDeliveryOptionsByDeliveryCode($deliveryCode);

        foreach (self::REQUIRE_OPTIONS as $configOption) {
            $option = '';
            foreach ($baseOptions as $baseOption) {
                if ($configOption == $baseOption->getCode() && !empty($baseOption->getValue())) {
                    $option = trim($baseOption->getValue());
                    break;
                }
            }

            if (empty($option)) {
                throw new DeliveriesException('CSE Api Error: The option ' . $configOption . ' is missing in the table delivery_options or its value is empty');
            }

            $options[$configOption] = $option;
        }

        return $options;
    }
}