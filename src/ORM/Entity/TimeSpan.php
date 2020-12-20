<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\ORM\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Refugis\DoctrineExtra\TimeSpan\TimeSpanTrait;

/**
 * Represents a time span with a start time and an end time.
 * Start and end times are optional.
 *
 * @ORM\Embeddable()
 */
class TimeSpan
{
    use TimeSpanTrait;

    /** @ORM\Column(type="datetimetz_immutable", nullable=true) */
    private ?DateTimeImmutable $start; // phpcs:ignore SlevomatCodingStandard.Classes.UnusedPrivateElements.UnusedProperty

    /** @ORM\Column(type="datetimetz_immutable", nullable=true) */
    private ?DateTimeImmutable $end; // phpcs:ignore SlevomatCodingStandard.Classes.UnusedPrivateElements.UnusedProperty
}
