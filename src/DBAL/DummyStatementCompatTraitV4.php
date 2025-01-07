<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\DBAL;

use Doctrine\DBAL\ParameterType;

trait DummyStatementCompatTraitV4
{
    public function bindValue(int|string $param, mixed $value, ParameterType $type): void
    {
        // TODO
    }
}
