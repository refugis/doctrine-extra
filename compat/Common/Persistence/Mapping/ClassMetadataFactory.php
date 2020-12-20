<?php declare(strict_types=1);

namespace Doctrine\Persistence\Mapping;

if (! \interface_exists(__NAMESPACE__.'\ClassMetadataFactory') && \interface_exists(\Doctrine\Common\Persistence\Mapping\ClassMetadataFactory::class)) {
    \class_alias(
        \Doctrine\Common\Persistence\Mapping\ClassMetadataFactory::class,
        __NAMESPACE__.'\ClassMetadataFactory'
    );

    if (false) {
        interface ClassMetadataFactory extends \Doctrine\Common\Persistence\Mapping\ClassMetadataFactory
        {
        }
    }
}
