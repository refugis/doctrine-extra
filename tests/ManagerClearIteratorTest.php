<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\Tests;

use Doctrine\Persistence\ObjectManager;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Refugis\DoctrineExtra\ManagerClearIterator;
use PHPUnit\Framework\TestCase;
use Refugis\DoctrineExtra\ObjectIteratorInterface;

class ManagerClearIteratorTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = $this->prophesize(ObjectManager::class);
    }

    public function testClearShouldBeCalled(): void
    {
        $i = 1250;
        $j = 0;

        $this->objectManager->clear()->shouldBeCalledTimes(2);

        $inner = $this->prophesize(ObjectIteratorInterface::class);
        $inner->valid()->will(function () use (&$i) {
            return $i > 0;
        });
        $inner->current()->willReturn(new \stdClass());
        $inner->rewind()->will(function () {});
        $inner->next()->will(function () use (&$i) {
            $i--;
        });
        $inner->getObjectManager()->willReturn($this->objectManager);

        $iterator = new ManagerClearIterator($inner->reveal());
        foreach ($iterator as $_) {
            $j++;
        }

        self::assertEquals(1250, $j);
    }
}
