<?php declare(strict_types=1);

namespace Doctrine\Persistence\Mapping\Driver;

if (! \interface_exists(__NAMESPACE__.'\MappingDriver') && \interface_exists(\Doctrine\Common\Persistence\Mapping\Driver\MappingDriver::class)) {
    \class_alias(
        \Doctrine\Common\Persistence\Mapping\Driver\MappingDriver::class,
        __NAMESPACE__.'\MappingDriver'
    );

    if (false) {
        interface MappingDriver extends \Doctrine\Common\Persistence\Mapping\Driver\MappingDriver
        {
        }
    }
}
