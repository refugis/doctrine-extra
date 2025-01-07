<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\DBAL;

use Doctrine\DBAL\ParameterType;

trait DummyStatementCompatTraitV2
{
    /**
     * {@inheritDoc}
     */
    public function bindValue($param, $value, $type = ParameterType::STRING): bool
    {
        // TODO

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function bindParam($column, &$variable, $type = ParameterType::STRING, $length = null): bool /** @phpstan-ignore-line */
    {
        // TODO

        return true;
    }
}
