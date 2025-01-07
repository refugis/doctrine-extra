<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\DBAL;

use function array_values;
use function reset;

trait DummyResultCompatTraitV2
{
    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function fetchAssociative()
    {
        return $this->doFetch();
    }

    /**
     * {@inheritDoc}
     */
    public function fetchOne()
    {
        $row = $this->doFetch();

        if ($row === false) {
            return false;
        }

        return reset($row);
    }
}
