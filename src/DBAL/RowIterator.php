<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\DBAL;

use ArrayIterator;
use BadMethodCallException;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\Persistence\ObjectManager;
use Refugis\DoctrineExtra\ObjectIteratorInterface;

class RowIterator implements ObjectIteratorInterface
{
    use IteratorTrait {
        current as private iteratorCurrent;
    }

    private ArrayIterator $internalIterator;

    public function __construct(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = clone $queryBuilder;
        $this->totalCount = null;

        $this->apply();
    }

    public function next(): void
    {
        $this->current = null;

        $iterator = $this->getIterator();

        $iterator->next();
        $this->currentElement = $iterator->valid() ? $iterator->current() : null;

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

    /**
     * {@inheritDoc}
     *
     * @return mixed
     */
    public function current(): mixed
    {
        $this->getIterator();

        return $this->iteratorCurrent();
    }

    public function rewind(): void
    {
        $this->current = null;
        $this->getIterator()->rewind();
        $this->currentElement = $this->internalIterator->current();
    }

    /**
     * Gets the iterator.
     */
    private function getIterator(): ArrayIterator
    {
        if (isset($this->internalIterator)) {
            return $this->internalIterator;
        }

        $result = $this->queryBuilder->executeQuery();
        $iterator = new ArrayIterator($result->fetchAllAssociative());

        $this->internalIterator = $iterator;
        $this->currentElement = $this->internalIterator->current();

        return $this->internalIterator;
    }

    public function getObjectManager(): ObjectManager
    {
        throw new BadMethodCallException('Cannot retrieve the object manager from a DBAL iterator');
    }
}
