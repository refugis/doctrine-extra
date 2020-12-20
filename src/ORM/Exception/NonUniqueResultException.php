<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\ORM\Exception;

use Doctrine\ORM\NonUniqueResultException as Base;
use Refugis\DoctrineExtra\Exception\NonUniqueResultExceptionInterface;

class NonUniqueResultException extends Base implements NonUniqueResultExceptionInterface
{
}
