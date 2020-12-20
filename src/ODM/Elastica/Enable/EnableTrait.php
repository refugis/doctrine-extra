<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\ODM\Elastica\Enable;

use Refugis\DoctrineExtra\Enable\EnableTrait as BaseTrait;
use Refugis\ODM\Elastica\Annotation as ODM;

trait EnableTrait
{
    use BaseTrait;

    /**
     * Whether the object is enabled or not.
     *
     * @ODM\Field(type="boolean")
     */
    private bool $enabled;
}
