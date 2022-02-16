<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\DBAL;

use ArrayIterator;
use Doctrine\DBAL\Driver\FetchUtils;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\ParameterType;
use IteratorAggregate;

use function array_values;
use function count;
use function reset;

/**
 * Dummy statement serves a static result statement
 * for DBAL connection mocks.
 *
 * Use for testing purpose only.
 */
class DummyStatement implements IteratorAggregate, Statement, Result
{
    /** @var mixed[] */
    private array $data;
    private int $columnCount;
    private int $num;

    /**
     * @param mixed[] $data
     */
    public function __construct(array $data, ?int $columnCount = null)
    {
        $this->data = $data;
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
     * {@inheritdoc}
     */
    public function fetchNumeric()
    {
        $row = $this->doFetch();

        if ($row === false) {
            return false;
        }

        return array_values($row);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAssociative()
    {
        return $this->doFetch();
    }

    /**
     * {@inheritdoc}
     */
    public function fetchOne()
    {
        $row = $this->doFetch();

        if ($row === false) {
            return false;
        }

        return reset($row);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAllNumeric(): array
    {
        return FetchUtils::fetchAllNumeric($this);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAllAssociative(): array
    {
        return FetchUtils::fetchAllAssociative($this);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchFirstColumn(): array
    {
        return FetchUtils::fetchFirstColumn($this);
    }

    public function free(): void
    {
        $this->data = [];
    }

    /**
     * {@inheritdoc}
     */
    public function bindValue($param, $value, $type = ParameterType::STRING): bool
    {
        // TODO

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function bindParam($column, &$variable, $type = ParameterType::STRING, $length = null): bool
    {
        // TODO

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($params = null): Result
    {
        return new DummyResult($this->data);
    }

    public function rowCount(): int
    {
        return count($this->data);
    }

    /**
     * @return mixed|false
     */
    private function doFetch()
    {
        if (! isset($this->data[$this->num])) {
            return false;
        }

        return $this->data[$this->num++];
    }
}
