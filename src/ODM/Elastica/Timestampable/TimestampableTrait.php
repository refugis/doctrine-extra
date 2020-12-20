<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\ODM\Elastica\Timestampable;

use DateTimeInterface;
use Refugis\DoctrineExtra\Timestampable\TimestampableTrait as BaseTrait;
use Refugis\ODM\Elastica\Annotation as ODM;

trait TimestampableTrait
{
    use BaseTrait;

    /** @ODM\Field(type="datetime_immutable") */
    private DateTimeInterface $createdAt;

    /** @ODM\Field(type="datetime_immutable") */
    private DateTimeInterface $updatedAt;
}
