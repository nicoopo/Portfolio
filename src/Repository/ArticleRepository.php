<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    /**
     * Articles publiés (date de publication passée), le plus récent d'abord : brouillons et articles programmés exclus.
     *
     * @return list<Article>
     */
    public function findPublies(): array
    {
        return $this->createQueryBuilder('article')
            ->where('article.publieLe <= :maintenant')
            ->setParameter('maintenant', new \DateTimeImmutable())
            ->orderBy('article.publieLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPublie(string $slug): ?Article
    {
        return $this->createQueryBuilder('article')
            ->where('article.slug = :slug AND article.publieLe <= :maintenant')
            ->setParameter('slug', $slug)
            ->setParameter('maintenant', new \DateTimeImmutable())
            ->getQuery()
            ->getOneOrNullResult();
    }
}
