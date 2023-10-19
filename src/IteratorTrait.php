<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra;

use Doctrine\Persistence\ObjectManager;

use function call_user_func;

trait IteratorTrait
{
    /**
     * A function to be applied to each element while iterating.
     *
     * @var callable
     */
    private $callable;

    /**
     * The current element from the underlying iterator.
     */
    private mixed $currentElement;

    /**
     * The current element, which results by the application
     * of the apply function.
     */
    private mixed $current;

    public function apply(callable|null $callable = null): ObjectIteratorInterface
    {
        if ($callable === null) {
            $callable = static function ($val) {
                return $val;
            };
        }

        $this->current = null;
        $this->callable = $callable;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @return mixed
     */
    public function current(): mixed
    {
        if (! $this->valid()) {
            return null;
        }

        if ($this->current === null) {
            $this->current = call_user_func($this->callable, $this->currentElement);
        }

        return $this->current;
    }

    /**
     * Checks if current position is valid.
     */
    abstract public function valid(): bool;

    /**
     * Retrieve the object manager for the current iterator.
     */
    abstract public function getObjectManager(): ObjectManager;
}
