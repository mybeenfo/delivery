<?php

namespace Monastirevrf\DeliveryService\Repository;

use Monastirevrf\DeliveryService\Entity\DeliveryLocationTariff;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method DeliveryLocationTariff|null find($id, $lockMode = null, $lockVersion = null)
 * @method DeliveryLocationTariff|null findOneBy(array $criteria, array $orderBy = null)
 * @method DeliveryLocationTariff[]    findAll()
 * @method DeliveryLocationTariff[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DeliveryLocationTariffRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, DeliveryLocationTariff::class);
    }
}
