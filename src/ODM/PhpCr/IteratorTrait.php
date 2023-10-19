<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\ODM\PhpCr;

use Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder;
use Doctrine\ODM\PHPCR\Query\Query;
use Doctrine\Persistence\ObjectManager;
use PHPCR\Query\QueryResultInterface;
use Refugis\DoctrineExtra\IteratorTrait as BaseIteratorTrait;

use function assert;
use function count;

trait IteratorTrait
{
    use BaseIteratorTrait;

    private QueryBuilder $queryBuilder;
    private int|null $totalCount;

    public function count(): int
    {
        if ($this->totalCount === null) {
            $queryBuilder = clone $this->queryBuilder;

            /* @phpstan-ignore-next-line */
            $queryBuilder->setMaxResults(null);
            /* @phpstan-ignore-next-line */
            $queryBuilder->setFirstResult(null);

            $result = $queryBuilder->getQuery()->getResult(Query::HYDRATE_PHPCR);

            /* @phpstan-ignore-next-line */
            assert($result instanceof QueryResultInterface);
            $this->totalCount = count($result->getRows());
        }

        return $this->totalCount;
    }

    public function getObjectManager(): ObjectManager
    {
        $queryBuilder = clone $this->queryBuilder;

        return (function (): ObjectManager {
            /* @phpstan-ignore-next-line */
            return $this->dm;
        })->bindTo($queryBuilder->getQuery(), Query::class)();
    }
}
