<?php

namespace Doctrine\Common\Persistence;

use function class_alias;
use function class_exists;

if (! interface_exists(__NAMESPACE__.'\ObjectRepository') && interface_exists(\Doctrine\Persistence\ObjectRepository::class)) {
    class_alias(
        \Doctrine\Persistence\ObjectRepository::class,
        __NAMESPACE__.'\ObjectRepository'
    );

    if (false) {
        interface ObjectRepository extends \Doctrine\Persistence\ObjectRepository
        {
        }
    }
}
