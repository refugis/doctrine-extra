<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\DBAL;

use Doctrine\DBAL\Driver\FetchUtils;
use Doctrine\DBAL\Driver\Result;

use function array_keys;
use function count;

/** @internal The class is internal to the caching layer implementation. */
final class DummyResult implements Result
{
    use DummyResultCompatTrait;

    private int $columnCount = 0;
    /** @var string[] */
    private array $columnNames = [];
    private int $num = 0;

    /**
     * @param mixed[] $data
     * @param string[]|null $columnNames
     */
    public function __construct(private array $data, int|null $columnCount = null, array|null $columnNames = null)
    {
        if (count($data) === 0) {
            return;
        }

        $this->columnCount = $columnCount ?? count($data[0]);
        $this->columnNames = $columnNames ?? array_keys($data[0]); /** @phpstan-ignore-line */
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAllNumeric(): array
    {
        return FetchUtils::fetchAllNumeric($this);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAllAssociative(): array
    {
        return FetchUtils::fetchAllAssociative($this);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchFirstColumn(): array
    {
        return FetchUtils::fetchFirstColumn($this);
    }

    public function rowCount(): int
    {
        return count($this->data);
    }

    public function columnCount(): int
    {
        return $this->columnCount;
    }

    public function getColumnName(int $index): string
    {
        return $this->columnNames[$index] ?? '';
    }

    public function free(): void
    {
        $this->data = [];
    }

    private function doFetch(): mixed
    {
        if (! isset($this->data[$this->num])) {
            return false;
        }

        return $this->data[$this->num++];
    }
}
