<?php declare(strict_types=1);

namespace Fazland\DoctrineExtra\ODM\Elastica\Enable;

use Fazland\DoctrineExtra\Enable\EnableTrait as BaseTrait;
use Fazland\ODM\Elastica\Annotation as ElasticaODM;

trait EnableTrait
{
    use BaseTrait;

    /**
     * Whether the object is enabled or not.
     *
     * @var bool
     *
     * @ElasticaODM\Field(type="boolean")
     */
    private $enabled;
}
