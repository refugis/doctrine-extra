<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\ODM\MongoDB\Exception;

use Doctrine\ODM\MongoDB\MongoDBException;
use Refugis\DoctrineExtra\Exception\NoResultExceptionInterface;

class NoResultException extends MongoDBException implements NoResultExceptionInterface
{
    public function __construct()
    {
        parent::__construct('No result was found for query although at least one document was expected.');
    }
}
