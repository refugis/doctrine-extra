<?php declare(strict_types=1);

namespace Doctrine\Persistence\Event;

if (! \class_exists(__NAMESPACE__.'\LifecycleEventArgs') && \class_exists(\Doctrine\Common\Persistence\Event\LifecycleEventArgs::class)) {
    \class_alias(
        \Doctrine\Common\Persistence\Event\LifecycleEventArgs::class,
        __NAMESPACE__.'\LifecycleEventArgs'
    );

    if (false) {
        class LifecycleEventArgs extends \Doctrine\Common\Persistence\Event\LifecycleEventArgs
        {
        }
    }
}
