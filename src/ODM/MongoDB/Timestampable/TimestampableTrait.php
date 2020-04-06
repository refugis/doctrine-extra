<?php declare(strict_types=1);

namespace Refugis\DoctrineExtra\ODM\MongoDB\Timestampable;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Refugis\DoctrineExtra\Timestampable\TimestampableTrait as BaseTrait;

trait TimestampableTrait
{
    use BaseTrait;

    /**
     * @ODM\Field(type="date")
     */
    private \DateTimeInterface $createdAt;

    /**
     * @ODM\Field(type="date")
     */
    private \DateTimeInterface $updatedAt;
}
