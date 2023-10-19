<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra;

use Countable;
use Doctrine\Persistence\ObjectManager;
use Iterator;

/**
 * @template T
 * @extends Iterator<T>
 */
interface ObjectIteratorInterface extends Iterator, Countable
{
    /**
     * Registers a callable to apply to each element of the iterator.
     *
     * @return $this
     */
    public function apply(callable|null $callable = null): self;

    /**
     * Retrieve the object manager for the current iterator.
     */
    public function getObjectManager(): ObjectManager;
}
