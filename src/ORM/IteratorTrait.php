<?php declare(strict_types=1);

namespace Refugis\DoctrineExtra\ORM;

use Doctrine\DBAL\FetchMode;
use Doctrine\ORM\QueryBuilder;
use Refugis\DoctrineExtra\IteratorTrait as BaseIteratorTrait;

trait IteratorTrait
{
    use BaseIteratorTrait;

    private QueryBuilder $queryBuilder;

    private ?int $totalCount;

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        if (null === $this->totalCount) {
            $queryBuilder = clone $this->queryBuilder;
            $alias = $queryBuilder->getRootAliases()[0];

            $queryBuilder->setFirstResult(null);
            $queryBuilder->setMaxResults(null);

            $queryBuilder->resetDQLPart('orderBy');

            $groupBy = $queryBuilder->getDQLPart('groupBy');
            if (! empty($groupBy)) {
                $dbalQb = $queryBuilder->getEntityManager()->getConnection()->createQueryBuilder();

                return (int) $dbalQb->select('COUNT(*)')
                    ->from('('.$queryBuilder->getQuery()->getSQL().') scrl_c_0')
                    ->execute()->fetch(FetchMode::COLUMN);
            }

            $distinct = $queryBuilder->getDQLPart('distinct') ? 'DISTINCT ' : '';
            $queryBuilder->resetDQLPart('distinct');

            $em = $queryBuilder->getEntityManager();
            $metadata = $em->getClassMetadata($queryBuilder->getRootEntities()[0]);

            if ($metadata->containsForeignIdentifier) {
                $alias = 'IDENTITY('.$alias.')';
            }

            $this->totalCount = (int) $queryBuilder->select('COUNT('.$distinct.$alias.')')
                ->getQuery()
                ->getSingleScalarResult()
            ;
        }

        return $this->totalCount;
    }
}
