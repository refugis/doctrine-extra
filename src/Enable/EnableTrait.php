<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\Enable;

/**
 * Represents a common implementation of EnableInterface.
 *
 * @property bool $enabled
 */
trait EnableTrait
{
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function enable(): EnableInterface
    {
        $this->enabled = true;

        return $this;
    }

    public function disable(): EnableInterface
    {
        $this->enabled = false;

        return $this;
    }
}
