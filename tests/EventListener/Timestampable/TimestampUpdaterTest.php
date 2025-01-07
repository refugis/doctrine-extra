<?php declare(strict_types=1);

namespace Refugis\DoctrineExtra\Tests\EventListener\Timestampable;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\DriverManager;
use Doctrine\ODM\MongoDB as MongoDBODM;
use Doctrine\ORM;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Refugis\DoctrineExtra\EventListener\Timestampable\TimestampUpdater;
use Refugis\DoctrineExtra\Tests\Fixtures\Entity\FooNonTimestampable;
use Refugis\DoctrineExtra\Tests\Fixtures\Entity\FooTimestampable;
use Refugis\DoctrineExtra\Timestampable\TimestampableInterface;
use Refugis\ODM\Elastica as ElasticaODM;

class TimestampUpdaterTest extends TestCase
{
    use ProphecyTrait;

    private TimestampUpdater $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->listener = new TimestampUpdater();
    }

    public function testPreUpdateShouldNotActOnNonTimestampableObjects(): void
    {
        $args = $this->prophesize(LifecycleEventArgs::class);

        $object = $this->prophesize(FooNonTimestampable::class);
        $args->getObject()->willReturn($object);

        $object->updateTimestamp()->shouldNotBeCalled();

        $this->listener->preUpdate($args->reveal());
    }

    public function testPreUpdateShouldUpdateTimestamp(): void
    {
        $args = $this->prophesize(LifecycleEventArgs::class);

        $object = $this->prophesize(TimestampableInterface::class);
        $args->getObject()->willReturn($object);

        $object->updateTimestamp()->shouldBeCalled();

        $this->listener->preUpdate($args->reveal());
    }

    /**
     * @group functional
     * @dataProvider metadataImplProvider
     */
    public function testListenerShouldWork(MappingDriver $mappingDriver): void
    {
        $eventManager = new EventManager();
        $events = [
            ORM\Events::preUpdate,
            MongoDBODM\Events::preUpdate,
            ElasticaODM\Events::preUpdate,
        ];
        $eventManager->addEventListener($events, new TimestampUpdater());

        if (class_exists(AnnotationRegistry::class)) {
            AnnotationRegistry::registerLoader('class_exists');
        }

        $configuration = new ORM\Configuration();
        $configuration->setMetadataDriverImpl($mappingDriver);
        $configuration->setProxyDir(\sys_get_temp_dir());
        $configuration->setProxyNamespace('__TMP__\\ProxyNamespace\\');

        $connectionParams = [
            'dbname' => 'mydb',
            'user' => 'user',
            'password' => 'secret',
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];
        $connection = DriverManager::getConnection($connectionParams, $configuration, $eventManager);
        $connection->executeQuery(<<<SQL
CREATE TABLE foo_timestampable (
    id INTEGER PRIMARY KEY,
    createdAt DATETIME NOT NULL,
    updatedAt DATETIME NOT NULL
)
SQL
        );

        $entityManager = new ORM\EntityManager($connection, $configuration, $eventManager);

        $foo = new FooTimestampable();
        $entityManager->persist($foo);
        $entityManager->flush();

        sleep(1);

        $createdAt = $foo->getCreatedAt();
        $updatedAt = $foo->getUpdatedAt();
        $foo->changeId();

        $entityManager->flush();

        self::assertEquals($createdAt, $foo->getCreatedAt());
        self::assertNotEquals($updatedAt, $foo->getUpdatedAt());
    }

    public function metadataImplProvider(): iterable
    {
        if (class_exists(ORM\Mapping\Driver\AnnotationDriver::class)) {
            yield [new ORM\Mapping\Driver\AnnotationDriver(new AnnotationReader())];
        }

        if (PHP_VERSION_ID >= 80000) {
            yield [new ORM\Mapping\Driver\AttributeDriver([])];
        }
    }
}
