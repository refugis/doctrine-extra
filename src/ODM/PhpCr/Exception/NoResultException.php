<?php declare(strict_types=1);

namespace Refugis\DoctrineExtra\ODM\PhpCr\Exception;

use Doctrine\ODM\PHPCR\Exception\RuntimeException;
use Refugis\DoctrineExtra\Exception\NoResultExceptionInterface;

class NoResultException extends RuntimeException implements NoResultExceptionInterface
{
    public function __construct()
    {
        parent::__construct('No result was found for query although at least one document was expected.');
    }
}
