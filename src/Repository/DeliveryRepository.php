<?php

namespace Monastirevrf\DeliveryService\Repository;

use Monastirevrf\DeliveryService\Entity\Delivery;
use Monastirevrf\DeliveryService\Entity\DeliveryType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Delivery|null find($id, $lockMode = null, $lockVersion = null)
 * @method Delivery|null findOneBy(array $criteria, array $orderBy = null)
 * @method Delivery[]    findAll()
 * @method Delivery[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DeliveryRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Delivery::class);
    }

    public function getActiveDeliveries(): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.active = :val')
            ->setParameter('val', true)
            ->orderBy('d.sort', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function addOrUpdate(DeliveryType $deliveryType, $params): ?Delivery
    {
        $entityManager = $this->getEntityManager();
        $delivery = $this->findOneBy(['code' => $params['code']]);

        if (empty($delivery)) {
            $delivery = new Delivery();

            $delivery->setDeliveryType($deliveryType)
                ->setName($params['name'])
                ->setCode($params['code'])
                ->setActive($params['active'])
                ->setSort($params['sort'])
                ->setDescription($params['description']);

            $entityManager->persist($delivery);
        }

        $entityManager->flush();

        return $delivery;
    }
}
