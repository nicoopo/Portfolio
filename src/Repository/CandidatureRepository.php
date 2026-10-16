<?php

namespace App\Repository;

use App\Entity\Candidature;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Candidature>
 */
class CandidatureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Candidature::class);
    }

    /** @return list<Candidature> date de relance passée, la plus ancienne d'abord */
    public function aRelancer(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.relancerLe <= :aujourdhui')
            ->orderBy('c.relancerLe')
            ->setParameter('aujourdhui', new \DateTimeImmutable('today'))
            ->getQuery()
            ->getResult();
    }

    public function compterARelancer(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c)')
            ->where('c.relancerLe <= :aujourdhui')
            ->setParameter('aujourdhui', new \DateTimeImmutable('today'))
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return list<Candidature> entretiens d'aujourd'hui et de demain, dans l'ordre */
    public function entretiensProches(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.entretienLe >= :aujourdhui AND c.entretienLe < :apresDemain')
            ->orderBy('c.entretienLe')
            ->setParameter('aujourdhui', new \DateTimeImmutable('today'))
            ->setParameter('apresDemain', new \DateTimeImmutable('today +2 days'))
            ->getQuery()
            ->getResult();
    }

    /**
     * Résumé du tableau de bord : réponses, entretiens, délai moyen de réponse (jours), liens recruteur joints et ouverts.
     *
     * @return array<string, mixed>
     */
    public function resume(): array
    {
        return $this->getEntityManager()->getConnection()->fetchAssociative(
            "SELECT COUNT(*) AS total,
                    COUNT(*) FILTER (WHERE c.statut NOT IN ('envoyee', 'relancee')) AS reponses,
                    COUNT(*) FILTER (WHERE c.statut IN ('entretien', 'acceptee')) AS entretiens,
                    ROUND(AVG(c.reponse_le - c.envoyee_le)) AS delai,
                    COUNT(c.lien_id) AS avec_lien,
                    COUNT(*) FILTER (WHERE l.visites > 0) AS liens_ouverts
             FROM candidature c LEFT JOIN lien_recruteur l ON l.id = c.lien_id",
        );
    }
}
