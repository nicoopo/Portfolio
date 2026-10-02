<?php

namespace App\Repository;

use App\Entity\DemandeContact;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DemandeContact>
 */
class DemandeContactRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DemandeContact::class);
    }

    public function compterDepuis(\DateTimeImmutable $date): int
    {
        return (int) $this->createQueryBuilder('demande')
            ->select('COUNT(demande.id)')
            ->where('demande.recuLe >= :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
