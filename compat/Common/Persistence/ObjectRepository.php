<?php declare(strict_types=1);

namespace Doctrine\Persistence;

if (! \interface_exists(__NAMESPACE__.'\ObjectRepository') && \interface_exists(\Doctrine\Common\Persistence\ObjectRepository::class)) {
    \class_alias(
        \Doctrine\Common\Persistence\ObjectRepository::class,
        __NAMESPACE__.'\ObjectRepository'
    );

    if (false) {
        interface ObjectRepository extends \Doctrine\Common\Persistence\ObjectRepository
        {
        }
    }
}
