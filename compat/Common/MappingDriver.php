<?php declare(strict_types=1);

namespace Doctrine\Common\Persistence\Mapping\Driver;

if (! \interface_exists(__NAMESPACE__.'\MappingDriver') && \interface_exists(\Doctrine\Persistence\Mapping\Driver\MappingDriver::class)) {
    \class_alias(
        \Doctrine\Persistence\Mapping\Driver\MappingDriver::class,
        __NAMESPACE__.'\MappingDriver'
    );

    if (false) {
        interface MappingDriver extends \Doctrine\Persistence\Mapping\Driver\MappingDriver
        {
        }
    }
}
