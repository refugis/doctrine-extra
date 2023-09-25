<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\ODM\MongoDB\Exception;

use Doctrine\ODM\MongoDB\MongoDBException;
use Refugis\DoctrineExtra\Exception\NonUniqueResultExceptionInterface;

class NonUniqueResultException extends MongoDBException implements NonUniqueResultExceptionInterface
{
    private const DEFAULT_MESSAGE = 'More than one result was found for query although one document or none was expected.';

    public function __construct(string|null $message = null)
    {
        parent::__construct($message ?? self::DEFAULT_MESSAGE);
    }
}
