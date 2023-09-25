<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\TimeSpan;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

use function Safe\sprintf;

/**
 * @property DateTimeImmutable|null $start
 * @property DateTimeImmutable|null $end
 */
trait TimeSpanTrait
{
    /**
     * Gets the period start time, if set.
     */
    public function getStart(): DateTimeImmutable|null
    {
        return $this->start;
    }

    /**
     * Gets the period end time, if set.
     */
    public function getEnd(): DateTimeImmutable|null
    {
        return $this->end;
    }

    /**
     * Updates the time span.
     */
    public function update(DateTimeImmutable|null $start, DateTimeImmutable|null $end): void
    {
        if ($start !== null && $end !== null && $start > $end) {
            throw new InvalidArgumentException('Start cannot be greater than end');
        }

        $this->start = $start;
        $this->end = $end;
    }

    /**
     * Checks whether the given date time is between the start and the end of
     * this time span. Null limits are treated as Infinite.
     */
    public function contains(DateTimeInterface $reference): bool
    {
        $reference = DateTimeImmutable::createFromInterface($reference);

        if ($this->start !== null && $reference < $this->start) {
            return false;
        }

        return $this->end === null || $reference < $this->end;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s-%s',
            $this->start?->format(DateTimeInterface::ATOM) ?? '',
            $this->end?->format(DateTimeInterface::ATOM) ?? '',
        );
    }
}
