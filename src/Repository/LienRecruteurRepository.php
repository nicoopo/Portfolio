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

    /** Le lien de ce code (/?pour=code), null pour un code vide ou inconnu */
    public function parCode(string $code): ?LienRecruteur
    {
        return '' !== $code ? $this->findOneBy(['code' => $code]) : null;
    }

    /**
     * Compte une ouverture du lien ; renvoie le nombre de visites, celle-ci comprise.
     * En une requête : deux ouvertures à la même seconde comptent bien deux visites, et une seule voit 1.
     */
    public function compterVisite(LienRecruteur $lien): int
    {
        return (int) $this->getEntityManager()->getConnection()->fetchOne(
            'UPDATE lien_recruteur SET visites = visites + 1, derniere_visite = CURRENT_TIMESTAMP(0), premiere_visite = COALESCE(premiere_visite, CURRENT_TIMESTAMP(0))
             WHERE id = :id RETURNING visites',
            ['id' => $lien->getId()],
        );
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
