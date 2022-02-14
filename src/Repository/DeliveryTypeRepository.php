<?php

namespace Monastirevrf\DeliveryService\Repository;

use Monastirevrf\DeliveryService\Entity\DeliveryType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method DeliveryType|null find($id, $lockMode = null, $lockVersion = null)
 * @method DeliveryType|null findOneBy(array $criteria, array $orderBy = null)
 * @method DeliveryType[]    findAll()
 * @method DeliveryType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DeliveryTypeRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, DeliveryType::class);
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function addOrUpdate(string $name, string $code): DeliveryType
    {
        $entityManager = $this->getEntityManager();
        $deliveryType = $this->findOneBy(['code' => $code]);

        if (empty($deliveryType)) {
            $deliveryType = new DeliveryType();
            $entityManager->persist($deliveryType);
        }

        $deliveryType->setName($name)
            ->setCode($code);

        $entityManager->flush();

        return $deliveryType;
    }
}
