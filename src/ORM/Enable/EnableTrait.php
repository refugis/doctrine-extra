<?php declare(strict_types=1);

namespace Refugis\DoctrineExtra\ORM\Enable;

use Doctrine\ORM\Mapping as ORM;
use Refugis\DoctrineExtra\Enable\EnableTrait as BaseTrait;

trait EnableTrait
{
    use BaseTrait;

    /**
     * Whether the object is enabled or not.
     *
     * @ORM\Column(type="boolean")
     */
    private bool $enabled;
}
