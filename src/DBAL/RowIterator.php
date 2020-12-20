<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\DBAL;

use ArrayIterator;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Query\QueryBuilder;
use Refugis\DoctrineExtra\ObjectIteratorInterface;

use function assert;
use function method_exists;

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
     * {@inheritdoc}
     *
     * @return mixed
     */
    public function current()
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

        $stmt = $this->queryBuilder->execute();
        assert($stmt instanceof ResultStatement);

        if (method_exists($stmt, 'fetchAllAssociative')) {
            $iterator = new ArrayIterator($stmt->fetchAllAssociative());
        } else {
            $iterator = new ArrayIterator($stmt->fetchAll(FetchMode::ASSOCIATIVE));
        }

        $this->internalIterator = $iterator;
        $this->currentElement = $this->internalIterator->current();

        return $this->internalIterator;
    }
}
