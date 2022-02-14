<?php

namespace Monastirevrf\DeliveryService\Repository;

use Monastirevrf\DeliveryService\Entity\Delivery;
use Monastirevrf\DeliveryService\Entity\DeliveryOption;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;

/**
 * @method DeliveryOption|null find($id, $lockMode = null, $lockVersion = null)
 * @method DeliveryOption|null findOneBy(array $criteria, array $orderBy = null)
 * @method DeliveryOption[]    findAll()
 * @method DeliveryOption[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DeliveryOptionRepository extends ServiceEntityRepository
{
    private const CACHE_TTL = 86400;

    private $cachePool;

    public function __construct(RegistryInterface $registry, AdapterInterface $cacheAdapter)
    {
        parent::__construct($registry, DeliveryOption::class);

        $this->cachePool = $cacheAdapter;
    }

    public function getDeliveryOptionsByDeliveryCode(string $deliveryCode): array
    {
        $cacheOptions = $this->cachePool->getItem($deliveryCode);
        $cacheOptions->expiresAfter(self::CACHE_TTL);

        if ($cacheOptions->isHit() === true) {
            return $cacheOptions->get();
        }

        $qb = $this->createQueryBuilder('d')
            ->join('d.delivery', 'dd', Join::WITH)
            ->andWhere('dd.code = :deliveryCode')
            ->setParameter('deliveryCode', $deliveryCode)
            ->getQuery();

        $cacheOptions->set($qb->getResult());
        $this->cachePool->save($cacheOptions);

        return $cacheOptions->get();
    }

    public function addOrUpdate(Delivery $delivery, $params): ?DeliveryOption
    {
        $entityManager = $this->getEntityManager();
        $option = $this->findOneBy([
            'code' => $params['code']
        ]);

        if (empty($option)) {
            $option = new DeliveryOption();

            $option->setDelivery($delivery)
                ->setName($params['name'])
                ->setCode($params['code'])
                ->setValue($params['value']);

            $entityManager->persist($option);
        }

        $entityManager->flush();

        return $option;
    }
}
