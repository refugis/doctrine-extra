<?php declare(strict_types=1);

namespace Refugis\DoctrineExtra\Exception;

/**
 * Exception thrown when a result is expected, but no rows are returned by the source.
 */
interface NoResultExceptionInterface extends \Throwable
{
}
