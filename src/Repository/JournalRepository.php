<?php

namespace App\Repository;

use App\Entity\Journal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Journal>
 */
class JournalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Journal::class);
    }

    /** Supprime les entrées antérieures à $limite ; renvoie leur nombre */
    public function purgerAvant(\DateTimeImmutable $limite): int
    {
        return $this->createQueryBuilder('journal')
            ->delete()
            ->where('journal.date < :limite')
            ->setParameter('limite', $limite)
            ->getQuery()
            ->execute();
    }
}
