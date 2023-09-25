<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra;

use Countable;
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
}
