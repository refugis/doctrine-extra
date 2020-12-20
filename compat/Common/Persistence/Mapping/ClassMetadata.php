<?php declare(strict_types=1);

namespace Doctrine\Persistence\Mapping;

if (! \interface_exists(__NAMESPACE__.'\ClassMetadata') && \interface_exists(\Doctrine\Common\Persistence\Mapping\ClassMetadata::class)) {
    \class_alias(
        \Doctrine\Common\Persistence\Mapping\ClassMetadata::class,
        __NAMESPACE__.'\ClassMetadata'
    );

    if (false) {
        interface ClassMetadata extends \Doctrine\Common\Persistence\Mapping\ClassMetadata
        {
        }
    }
}
