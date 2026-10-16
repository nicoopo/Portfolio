<?php

namespace App\Repository;

use App\Entity\LienRecruteur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LienRecruteur>
 */
class LienRecruteurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LienRecruteur::class);
    }

    /** @return list<string> entreprises dont le lien a été ouvert depuis $date, la plus récente d'abord */
    public function entreprisesOuvertesDepuis(\DateTimeImmutable $date): array
    {
        return $this->createQueryBuilder('l')
            ->select('l.entreprise')
            ->where('l.derniereVisite >= :date')
            ->orderBy('l.derniereVisite', 'DESC')
            ->setParameter('date', $date)
            ->getQuery()
            ->getSingleColumnResult();
    }
}
