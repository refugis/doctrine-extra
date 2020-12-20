<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\Exception;

use Throwable;

/**
 * Exception thrown when a query unexpectedly returns more than one result.
 */
interface NonUniqueResultExceptionInterface extends Throwable
{
}
