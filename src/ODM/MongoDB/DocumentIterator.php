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
        $this->internalIterator = $this->queryBuilder->getQuery()->getIterator();
        $this->totalCount = null;

        $this->apply();
        $this->currentElement = $this->internalIterator->current()[0];
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
        $this->internalIterator->next();

        $this->current = null;
        $this->currentElement = $this->internalIterator->current();

        $this->current();
    }

    public function key(): int
    {
        return $this->internalIterator->key();
    }

    public function valid(): bool
    {
        return $this->internalIterator->valid();
    }

    public function rewind(): void
    {
        $this->current = null;
        $this->internalIterator->rewind();
        $this->currentElement = $this->internalIterator->current();
    }
}
