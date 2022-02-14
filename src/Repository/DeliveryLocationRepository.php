<?php

namespace Monastirevrf\DeliveryService\Repository;

use Monastirevrf\DeliveryService\Entity\DeliveryLocation;
use Symfony\Bridge\Doctrine\RegistryInterface;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method DeliveryLocation|null find($id, $lockMode = null, $lockVersion = null)
 * @method DeliveryLocation|null findOneBy(array $criteria, array $orderBy = null)
 * @method DeliveryLocation[]    findAll()
 * @method DeliveryLocation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DeliveryLocationRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, DeliveryLocation::class);
    }

    /**
     * Писк записей в таблице delivery_delivery_locations
     */
    public function findActiveDeliveries(string $deliveryCode, array $geoParams, array $tradePointIds): array
    {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.delivery', 'dd', Join::WITH, 'dd.code = :deliveryCode')
            ->andWhere('d.active = :dActive')
            ->andWhere('dd.active = :ddActive')
            ->setParameter('deliveryCode', $deliveryCode)
            ->setParameter('dActive', true)
            ->setParameter('ddActive', true);

        foreach ($geoParams as $param => $value) {
            if (!empty($value)) {
                $qb->andWhere('d.' . $param . '= :' . $param);
                $qb->setParameter($param, $value);
            }
        }

        if (!empty($tradePointIds)) {
            $qb->join('d.deliveryLocationTariffs', 'dlt', Join::WITH);
            $qb->addSelect('dlt');
            $qb->andWhere('dlt.tradePointId IN (' . implode(',', $tradePointIds) . ')');
            $qb->andWhere('dlt.active = :dltActive');
            $qb->setParameter('dltActive', true);
        }

        return $qb->getQuery()->getResult();
    }
}
