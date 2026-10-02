<?php

namespace App\Repository;

use App\Entity\CategorieCompetence;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CategorieCompetence>
 */
class CategorieCompetenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CategorieCompetence::class);
    }

    /**
     * Catégories avec leurs compétences et les projets de chacune, en une seule requête.
     *
     * @return list<CategorieCompetence>
     */
    public function findAllWithCompetences(): array
    {
        return $this->createQueryBuilder('categorie')
            ->addSelect('competence', 'projet')
            ->join('categorie.competences', 'competence')
            ->leftJoin('competence.projets', 'projet')
            ->orderBy('categorie.position')
            ->addOrderBy('competence.position')
            ->addOrderBy('projet.position')
            ->getQuery()
            ->getResult();
    }
}
