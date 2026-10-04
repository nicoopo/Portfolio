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

    /** Supprime les demandes reçues avant $limite ; renvoie leur nombre */
    public function purgerAvant(\DateTimeImmutable $limite): int
    {
        return $this->createQueryBuilder('demande')
            ->delete()
            ->where('demande.recuLe < :limite')
            ->setParameter('limite', $limite)
            ->getQuery()
            ->execute();
    }
}
