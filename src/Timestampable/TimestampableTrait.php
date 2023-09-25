<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\Timestampable;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * @property DateTimeInterface $createdAt
 * @property DateTimeInterface $updatedAt
 */
trait TimestampableTrait
{
    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function updateTimestamp(): TimestampableInterface
    {
        $this->updatedAt = new DateTimeImmutable();

        return $this;
    }
}
