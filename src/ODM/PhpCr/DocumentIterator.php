<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\ODM\PhpCr;

use ArrayIterator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder;
use Doctrine\ODM\PHPCR\Query\QueryException;
use Iterator;
use Refugis\DoctrineExtra\ObjectIteratorInterface;

use function array_values;
use function assert;

/**
 * This class allows iterating a query iterator for a single entity query.
 */
class DocumentIterator implements ObjectIteratorInterface
{
    use IteratorTrait;

    /** @var Iterator<object> */
    private Iterator $internalIterator;

    public function __construct(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = clone $queryBuilder;
        $this->totalCount = null;

        $this->apply();
    }

    public function next(): void
    {
        $this->getIterator()->next();

        $this->current = null;
        $this->currentElement = $this->getIterator()->current();

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
        $this->currentElement = $this->getIterator()->current();
    }

    /**
     * Gets the iterator.
     *
     * @return Iterator<object>
     */
    private function getIterator(): Iterator
    {
        if (isset($this->internalIterator)) {
            return $this->internalIterator;
        }

        $query = $this->queryBuilder->getQuery();

        try {
            /* @phpstan-ignore-next-line */
            $this->internalIterator = $query->iterate();
        } catch (QueryException $e) {
            $result = $query->getResult();

            /* @phpstan-ignore-next-line */
            assert($result instanceof ArrayCollection);
            $this->internalIterator = new ArrayIterator(array_values($result->toArray()));
        }

        assert($this->internalIterator instanceof Iterator);
        /* @phpstan-ignore-next-line */
        $this->currentElement = $this->internalIterator->current();

        return $this->internalIterator;
    }
}
