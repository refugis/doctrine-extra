<?php declare(strict_types=1);

namespace Doctrine\Persistence\Mapping;

if (! \class_exists(__NAMESPACE__.'\RuntimeReflectionService') && \class_exists(\Doctrine\Common\Persistence\Mapping\RuntimeReflectionService::class)) {
    \class_alias(
        \Doctrine\Common\Persistence\Mapping\RuntimeReflectionService::class,
        __NAMESPACE__.'\RuntimeReflectionService'
    );

    if (false) {
        class RuntimeReflectionService extends \Doctrine\Common\Persistence\Mapping\RuntimeReflectionService
        {
        }
    }
}
