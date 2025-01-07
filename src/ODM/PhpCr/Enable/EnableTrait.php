<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\ODM\PhpCr\Enable;

use Doctrine\ODM\PHPCR\Mapping\Annotations as ODM;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCRAttributes;
use Refugis\DoctrineExtra\Enable\EnableTrait as BaseTrait;

trait EnableTrait
{
    use BaseTrait;

    /**
     * Whether the object is enabled or not.
     *
     * @ODM\Field(type="boolean")
     */
    #[PHPCRAttributes\Field(type: 'boolean')]
    private bool $enabled;
}
