<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\ORM\Timestampable;

use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Refugis\DoctrineExtra\Timestampable\TimestampableTrait as BaseTrait;

trait TimestampableTrait
{
    use BaseTrait;

    /** @ORM\Column(type="datetimetz_immutable") */
    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private DateTimeInterface $createdAt;

    /** @ORM\Column(type="datetimetz_immutable") */
    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private DateTimeInterface $updatedAt;
}
