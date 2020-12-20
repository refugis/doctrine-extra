<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\ODM\PhpCr\Timestampable;

use Cake\Chronos\MutableDateTime;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ODM\PHPCR\Mapping\Annotations as ODM;
use Refugis\DoctrineExtra\Timestampable\TimestampableInterface;
use Refugis\DoctrineExtra\Timestampable\TimestampableTrait as BaseTrait;

/**
 * Unfortunately PHPCR only supports mutable DateTime objects.
 * Set property types to mutable DateTime to avoid errors during initialization,
 * but return immutable object from getters.
 */
trait TimestampableTrait
{
    use BaseTrait;

    /** @ODM\Field(type="date") */
    private DateTime $createdAt;

    /** @ODM\Field(type="date") */
    private DateTime $updatedAt;

    public function getCreatedAt(): DateTimeInterface
    {
        return DateTimeImmutable::createFromMutable($this->createdAt);
    }

    public function getUpdatedAt(): DateTimeInterface
    {
        return DateTimeImmutable::createFromMutable($this->updatedAt);
    }

    public function updateTimestamp(): TimestampableInterface
    {
        $this->updatedAt = MutableDateTime::now();

        return $this;
    }
}
