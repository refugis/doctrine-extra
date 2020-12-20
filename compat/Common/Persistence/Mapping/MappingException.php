<?php declare(strict_types=1);

namespace Doctrine\Persistence\Mapping;

if (! \class_exists(__NAMESPACE__.'\MappingException') && \class_exists(\Doctrine\Common\Persistence\Mapping\MappingException::class)) {
    \class_alias(
        \Doctrine\Common\Persistence\Mapping\MappingException::class,
        __NAMESPACE__.'\MappingException'
    );

    if (false) {
        class MappingException extends \Doctrine\Common\Persistence\Mapping\MappingException
        {
        }
    }
}
