<?php

namespace Monastirevrf\DeliveryService\Controller;

use Exception;

use Monastirevrf\DeliveryService\Entity\Delivery;

use Monastirevrf\DeliveryService\Repository\DeliveryLocationRepository;
use Monastirevrf\DeliveryService\Repository\DeliveryOptionRepository;
use Monastirevrf\DeliveryService\Exceptions\DeliveriesException;
use Monastirevrf\DeliveryService\Service\Deliveries\DeliveriesFactory;
use Monastirevrf\DeliveryService\Service\Deliveries\DeliveryParams;

use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DeliveriesController extends AbstractController
{
    /**
     * @var DeliveryLocationRepository
     */
    private $deliveryLocationRepository;

    /**
     * @var DeliveryOptionRepository
     */
    private $deliveryOptionRepository;

    /**
     * @var AdapterInterface
     */
    private $cacheAdapter;

    public function __construct(
        DeliveryLocationRepository $deliveryLocationRepository,
        DeliveryOptionRepository $deliveryOptionRepository,
        AdapterInterface $cacheAdapter
    ){
        $this->cacheAdapter = $cacheAdapter;
        $this->deliveryLocationRepository = $deliveryLocationRepository;
        $this->deliveryOptionRepository = $deliveryOptionRepository;
    }

    /**
     * @Route("/delivery/availability")
     */
    public function getDeliveryAvailable(Request $request)
    {
        $deliveryParams = new DeliveryParams($request);

        if (empty($this->getDoctrine()->getRepository(Delivery::class)->findOneBy(['code' => $deliveryParams->getDeliveryCode()]))) {
            throw new DeliveriesException('Error: Delivery service not found by deliveryCode');
        }

        try {
            $deliveryObj = DeliveriesFactory::getDelivery($deliveryParams, $this->deliveryLocationRepository, $this->deliveryOptionRepository, $this->cacheAdapter);

            return [
                'success' => true,
                'deliveryAvailable' => $deliveryObj->getDeliveryAvailable(),
                'minCostOfDelivery' => $deliveryObj->getDeliveryMinPrice(),
                'minDateOfDelivery' => $deliveryObj->getDeliveryMinDate(),
            ];
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @Route("/delivery/calculate")
     */
    public function deliveryCalculate(Request $request)
    {
        $deliveryParams = new DeliveryParams($request);

        if (empty($this->getDoctrine()->getRepository(Delivery::class)->findOneBy(['code' => $deliveryParams->getDeliveryCode()]))) {
            throw new DeliveriesException('Error: Delivery service not found by deliveryCode');
        }

        try {
            $deliveryObj = DeliveriesFactory::getDelivery($deliveryParams, $this->deliveryLocationRepository, $this->deliveryOptionRepository, $this->cacheAdapter);

            return [
                'success' => true,
                'deliveryAvailable'    => $deliveryObj->getDeliveryAvailable(),
                'costOfDelivery'       => $deliveryObj->getDeliveryPrice(),
                'freeDeliverySumm'     => $deliveryObj->freeDeliverySumm(),
                'deliveryDateTime'     => $deliveryObj->deliveryDate(),
                'deliveryTradePointId' => $deliveryObj->deliveryTradePointId(),
            ];
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }
}