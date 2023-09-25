<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\DBAL;

use Doctrine\DBAL\Query\QueryBuilder;
use Refugis\DoctrineExtra\IteratorTrait as BaseIteratorTrait;

trait IteratorTrait
{
    use BaseIteratorTrait;

    private QueryBuilder $queryBuilder;
    private int|null $totalCount;

    public function count(): int
    {
        if ($this->totalCount === null) {
            $queryBuilder = clone $this->queryBuilder;
            $result = $queryBuilder->select('COUNT(*) AS sclr_0')
                ->setFirstResult(0)
                ->setMaxResults(null)
                ->executeQuery();

            $this->totalCount = (int) $result->fetchOne();
        }

        return $this->totalCount;
    }
}
