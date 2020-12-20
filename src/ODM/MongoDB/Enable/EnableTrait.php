<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\ODM\MongoDB\Enable;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDBODM;
use Refugis\DoctrineExtra\Enable\EnableTrait as BaseTrait;

trait EnableTrait
{
    use BaseTrait;

    /**
     * Whether the object is enabled or not.
     *
     * @MongoDBODM\Field(type="boolean")
     */
    private bool $enabled;
}
