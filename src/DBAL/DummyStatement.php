<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\DBAL;

use ArrayIterator;
use Doctrine\DBAL\Driver\FetchUtils;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use IteratorAggregate;

use function count;

/**
 * Dummy statement serves a static result statement
 * for DBAL connection mocks.
 *
 * Use for testing purpose only.
 */
class DummyStatement implements IteratorAggregate, Statement, Result
{
    use DummyStatementCompatTrait;

    private int $columnCount;
    private int $num;

    /** @param mixed[] $data */
    public function __construct(private array $data, int|null $columnCount = null)
    {
        $this->columnCount = $columnCount ?? count($data[0] ?? []);
        $this->num = 0;
    }

    public function closeCursor(): bool
    {
        unset($this->data);

        return true;
    }

    public function columnCount(): int
    {
        return $this->columnCount;
    }

    public function getIterator(): ArrayIterator
    {
        $data = $this->execute()->fetchAllAssociative();

        return new ArrayIterator($data);
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

    public function free(): void
    {
        $this->data = [];
    }

    public function execute(mixed $params = null): Result
    {
        return new DummyResult($this->data);
    }

    public function rowCount(): int
    {
        return count($this->data);
    }

    private function doFetch(): mixed
    {
        if (! isset($this->data[$this->num])) {
            return false;
        }

        return $this->data[$this->num++];
    }
}
