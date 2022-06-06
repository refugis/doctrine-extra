<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\TimeSpan;

use Cake\Chronos\Chronos;
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
    public function getStart(): ?DateTimeImmutable
    {
        return $this->start;
    }

    /**
     * Gets the period end time, if set.
     */
    public function getEnd(): ?DateTimeImmutable
    {
        return $this->end;
    }

    /**
     * Updates the time span.
     */
    public function update(?DateTimeImmutable $start, ?DateTimeImmutable $end): void
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
        $reference = Chronos::instance($reference);

        if ($this->start !== null && $reference->lt(Chronos::instance($this->start))) {
            return false;
        }

        return $this->end === null || ! $reference->gte(Chronos::instance($this->end));
    }

    public function __toString(): string
    {
        return sprintf(
            '%s-%s',
            $this->start !== null ? $this->start->format(DateTimeInterface::ATOM) : '',
            $this->end !== null ? $this->end->format(DateTimeInterface::ATOM) : ''
        );
    }
}
