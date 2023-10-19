<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra;

use Doctrine\Persistence\ObjectManager;

class ManagerClearIterator implements ObjectIteratorInterface
{
    private int $count = 0;

    public function __construct(
        private readonly ObjectIteratorInterface $iterator,
        private readonly int $batch = 500,
    ) {
    }

    public function current(): mixed
    {
        if (++$this->count % $this->batch === 0) {
            $this->iterator->getObjectManager()->clear();
        }

        return $this->iterator->current();
    }

    public function next(): void
    {
        $this->iterator->next();
    }

    public function key(): mixed
    {
        return $this->iterator->key();
    }

    public function valid(): bool
    {
        return $this->iterator->valid();
    }

    public function rewind(): void
    {
        $this->iterator->rewind();
    }

    public function count(): int
    {
        return $this->iterator->count();
    }

    public function apply(callable|null $callable = null): ObjectIteratorInterface
    {
        $this->iterator->apply($callable);

        return $this;
    }

    public function getObjectManager(): ObjectManager
    {
        return $this->iterator->getObjectManager();
    }
}
