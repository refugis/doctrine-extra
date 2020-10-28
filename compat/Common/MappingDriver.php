<?php

namespace Doctrine\Common\Persistence\Mapping\Driver;

use function class_alias;
use function class_exists;

if (! interface_exists(__NAMESPACE__.'\MappingDriver') && interface_exists(\Doctrine\Persistence\Mapping\Driver\MappingDriver::class)) {
    class_alias(
        \Doctrine\Persistence\Mapping\Driver\MappingDriver::class,
        __NAMESPACE__.'\MappingDriver'
    );

    if (false) {
        interface MappingDriver extends \Doctrine\Persistence\Mapping\Driver\MappingDriver
        {
        }
    }
}
