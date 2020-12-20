<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\ORM;

use Doctrine\ORM\QueryBuilder;
use Generator;
use InvalidArgumentException;
use Iterator;
use Refugis\DoctrineExtra\ObjectIteratorInterface;

use function assert;
use function count;
use function is_array;
use function method_exists;

/**
 * This class allows iterating a query iterator for a single entity query.
 */
class EntityIterator implements ObjectIteratorInterface
{
    use IteratorTrait {
        current as private iteratorCurrent;
    }

    /** @var Iterator<(false|array<object>|object|null)> */
    private Iterator $internalIterator;
    private ?string $resultCache;
    private int $cacheLifetime;

    public function __construct(QueryBuilder $queryBuilder)
    {
        if (count($queryBuilder->getRootAliases()) !== 1) {
            throw new InvalidArgumentException('QueryBuilder must have exactly one root aliases for the iterator to work.');
        }

        $this->queryBuilder = clone $queryBuilder;
        $this->resultCache = null;
        $this->totalCount = null;

        $this->apply();
    }

    public function next(): void
    {
        $this->current = null;

        $this->getIterator()->next();
        $next = $this->getIterator()->current();

        if ($next === false) {
            $this->currentElement = null;
        } elseif (is_array($next)) {
            $this->currentElement = $next[0] ?? null;
        } else {
            $this->currentElement = $next;
        }

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
        $this->currentElement = $this->getCurrentElement();
    }

    /**
     * Request to use query result cache.
     *
     * @return EntityIterator
     */
    public function useResultCache(bool $enable, string $cacheId, ?int $lifetime): self
    {
        if (! $enable) {
            $this->resultCache = null;

            return $this;
        }

        $this->resultCache = $cacheId;
        $this->cacheLifetime = $lifetime ?? 0;

        return $this;
    }

    /**
     * Gets the iterator.
     *
     * @return Iterator<false|array<object>|object>
     */
    private function getIterator(): Iterator
    {
        if (isset($this->internalIterator)) {
            return $this->internalIterator;
        }

        $query = $this->queryBuilder->getQuery();
        if ($this->resultCache !== null) {
            if (method_exists($query, 'enableResultCache')) {
                $query->enableResultCache($this->cacheLifetime, $this->resultCache);
            } else {
                $query->useResultCache(true, $this->cacheLifetime, $this->resultCache);
            }

            $iterator = $query->iterate();
        } else {
            $iterator = method_exists($query, 'toIterable') ? $query->toIterable() : $query->iterate();
        }

        if (! $iterator instanceof Iterator) {
            $iterator = (static function (iterable $iterable): Generator {
                yield from $iterable;
            })($iterator);
        }

        $this->internalIterator = $iterator;
        $this->currentElement = $this->getCurrentElement();

        return $this->internalIterator;
    }

    /**
     * @return mixed|null
     */
    private function getCurrentElement()
    {
        assert($this->internalIterator !== null);

        $current = $this->internalIterator->current();
        if ($current === null) {
            return $current;
        }

        if (is_array($current)) {
            return $current[0] ?? null;
        }

        return $current;
    }
}
