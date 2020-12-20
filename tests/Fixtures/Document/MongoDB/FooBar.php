<?php declare(strict_types=1);

namespace Refugis\DoctrineExtra\Tests\Fixtures\Document\MongoDB;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document()
 */
class FooBar
{
    /**
     * @ODM\Id()
     */
    public string $id;

    /**
     * @var mixed
     */
    public $prop;
}
