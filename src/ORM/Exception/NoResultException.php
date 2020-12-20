<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\ORM\Exception;

use Doctrine\ORM\NoResultException as Base;
use Refugis\DoctrineExtra\Exception\NoResultExceptionInterface;

class NoResultException extends Base implements NoResultExceptionInterface
{
}
