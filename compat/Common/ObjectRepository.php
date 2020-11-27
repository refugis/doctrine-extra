<?php declare(strict_types=1);

namespace Doctrine\Common\Persistence;

if (! \interface_exists(__NAMESPACE__.'\ObjectRepository') && \interface_exists(\Doctrine\Persistence\ObjectRepository::class)) {
    \class_alias(
        \Doctrine\Persistence\ObjectRepository::class,
        __NAMESPACE__.'\ObjectRepository'
    );

    if (false) {
        interface ObjectRepository extends \Doctrine\Persistence\ObjectRepository
        {
        }
    }
}
