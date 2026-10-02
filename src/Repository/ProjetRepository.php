<?php

namespace App\Repository;

use App\Entity\Projet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Projet>
 */
class ProjetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Projet::class);
    }

    /**
     * Projets regroupés par catégorie (dans l'ordre de leurs projets), avec leurs compétences.
     *
     * @return array<string, list<Projet>>
     */
    public function findAllByCategorie(): array
    {
        $projets = $this->createQueryBuilder('projet')
            ->addSelect('competence')
            ->leftJoin('projet.competences', 'competence')
            ->orderBy('projet.position')
            ->addOrderBy('competence.position')
            ->getQuery()
            ->getResult();

        $categories = [];
        foreach ($projets as $projet) {
            $categories[$projet->getCategorie()][] = $projet;
        }

        return $categories;
    }
}
