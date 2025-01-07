<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\DBAL;

use function array_values;
use function reset;

trait DummyResultCompatTraitV4
{
    public function fetchNumeric(): array|false
    {
        $row = $this->doFetch();

        if ($row === false) {
            return false;
        }

        return array_values($row);
    }

    public function fetchAssociative(): array|false
    {
        return $this->doFetch();
    }

    public function fetchOne(): mixed
    {
        $row = $this->doFetch();

        if ($row === false) {
            return false;
        }

        return reset($row);
    }
}
