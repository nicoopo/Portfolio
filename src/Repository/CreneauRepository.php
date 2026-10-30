<?php

namespace App\Repository;

use App\Entity\Creneau;
use App\Entity\LienRecruteur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Creneau>
 */
class CreneauRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Creneau::class);
    }

    /** Le prochain créneau réservé par ce lien (à partir d'aujourd'hui), ou null */
    public function reservePar(LienRecruteur $lien): ?Creneau
    {
        return $this->createQueryBuilder('c')
            ->where('c.lien = :lien AND c.debut >= :aujourdhui')
            ->orderBy('c.debut')
            ->setMaxResults(1)
            ->setParameter('lien', $lien)
            ->setParameter('aujourdhui', new \DateTimeImmutable('today'))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<Creneau> créneaux libres à plus de 12 heures, dans l'ordre */
    public function libres(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.lien IS NULL AND c.debut > :limite')
            ->orderBy('c.debut')
            ->setParameter('limite', new \DateTimeImmutable('+12 hours'))
            ->getQuery()
            ->getResult();
    }

    /** Bouton « Choisir un créneau » : un créneau libre à plus de 12 heures, ou déjà réservé par ce lien */
    public function proposablesA(LienRecruteur $lien): bool
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c)')
            ->where('(c.lien IS NULL AND c.debut > :limite) OR c.lien = :lien')
            ->setParameter('limite', new \DateTimeImmutable('+12 hours'))
            ->setParameter('lien', $lien)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    /**
     * Réserve le créneau pour ce lien s'il est encore libre et si le lien n'en a pas déjà un ; false sinon.
     * UPDATE conditionné : deux recruteurs ne prennent jamais le même créneau. Le lien est verrouillé d'abord :
     * deux envois du même lien à la même seconde (double-clic, deux onglets) ne réservent jamais deux créneaux.
     */
    public function reserver(Creneau $creneau, LienRecruteur $lien, string $nom, string $email): bool
    {
        return $this->getEntityManager()->getConnection()->transactional(function ($connection) use ($creneau, $lien, $nom, $email): bool {
            $connection->executeStatement('SELECT 1 FROM lien_recruteur WHERE id = :lien FOR UPDATE', ['lien' => $lien->getId()]);

            return 1 === $connection->executeStatement(
                'UPDATE creneau SET lien_id = :lien, contact_nom = :nom, contact_email = :email WHERE id = :id AND lien_id IS NULL
                 AND NOT EXISTS (SELECT 1 FROM creneau r WHERE r.lien_id = :lien AND r.debut >= :aujourdhui)',
                ['lien' => $lien->getId(), 'nom' => $nom, 'email' => $email, 'id' => $creneau->getId(), 'aujourdhui' => (new \DateTimeImmutable('today'))->format('Y-m-d H:i:s')],
            );
        });
    }
}
