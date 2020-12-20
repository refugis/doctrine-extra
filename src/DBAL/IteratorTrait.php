<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\DBAL;

use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\Query\QueryBuilder;
use Refugis\DoctrineExtra\IteratorTrait as BaseIteratorTrait;

use function assert;
use function method_exists;

trait IteratorTrait
{
    use BaseIteratorTrait;

    private QueryBuilder $queryBuilder;
    private ?int $totalCount;

    public function count(): int
    {
        if ($this->totalCount === null) {
            $queryBuilder = clone $this->queryBuilder;
            $stmt = $queryBuilder->select('COUNT(*) AS sclr_0')
                ->setFirstResult(0)
                ->setMaxResults(null)
                ->execute();

            assert($stmt instanceof ResultStatement);
            if (method_exists($stmt, 'fetchOne')) {
                $this->totalCount = (int) $stmt->fetchOne();
            } else {
                $this->totalCount = (int) $stmt->fetchColumn();
            }
        }

        return $this->totalCount;
    }
}
