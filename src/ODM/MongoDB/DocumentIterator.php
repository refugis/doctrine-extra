<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\ODM\MongoDB;

use Doctrine\ODM\MongoDB\Query\Builder;
use Iterator;
use Refugis\DoctrineExtra\IteratorTrait;
use Refugis\DoctrineExtra\ObjectIteratorInterface;

/**
 * This class allows iterating a query iterator for a single entity query.
 */
class DocumentIterator implements ObjectIteratorInterface
{
    use IteratorTrait;

    /** @var Iterator<object> */
    private Iterator $internalIterator;
    private Builder $queryBuilder;
    private ?int $totalCount;

    public function __construct(Builder $queryBuilder)
    {
        $this->queryBuilder = clone $queryBuilder;
        $this->totalCount = null;

        $this->apply();
    }

    public function count(): int
    {
        if ($this->totalCount === null) {
            $queryBuilder = clone $this->queryBuilder;
            $queryBuilder->count();

            /* @phpstan-ignore-next-line */
            $this->totalCount = (int) $queryBuilder->getQuery()->execute();
        }

        return $this->totalCount;
    }

    public function next(): void
    {
        $this->getIterator()->next();

        $this->current = null;
        $this->currentElement = $this->internalIterator->current();

        $this->current();
    }

    public function key(): int
    {
        return $this->getIterator()->key();
    }

    public function valid(): bool
    {
        return $this->getIterator()->valid();
    }

    public function rewind(): void
    {
        $this->current = null;
        $this->getIterator()->rewind();
        $this->currentElement = $this->internalIterator->current();
    }

    private function getIterator(): \Iterator
    {
        if (isset($this->internalIterator)) {
            return $this->internalIterator;
        }

        $this->internalIterator = $this->queryBuilder->getQuery()->getIterator();
        assert($this->internalIterator instanceof Iterator);

        $this->internalIterator->next();
        $this->currentElement = $this->internalIterator->current();

        return $this->internalIterator;
    }
}
