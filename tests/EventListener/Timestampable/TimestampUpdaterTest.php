<?php declare(strict_types=1);

namespace Refugis\DoctrineExtra\Tests\EventListener\Timestampable;

use Cake\Chronos\Chronos;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\DriverManager;
use Doctrine\ODM\MongoDB as MongoDBODM;
use Doctrine\ORM;
use Doctrine\Persistence\Event\LifecycleEventArgs;
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
     */
    public function testListenerShouldWork(): void
    {
        $eventManager = new EventManager();
        $events = [
            ORM\Events::preUpdate,
            MongoDBODM\Events::preUpdate,
            ElasticaODM\Events::preUpdate,
        ];
        $eventManager->addEventListener($events, new TimestampUpdater());

        AnnotationRegistry::registerLoader('class_exists');
        $configuration = new ORM\Configuration();
        $configuration->setMetadataDriverImpl(new ORM\Mapping\Driver\AnnotationDriver(new AnnotationReader()));
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

        $entityManager = ORM\EntityManager::create($connection, $configuration, $eventManager);

        Chronos::setTestNow('2018-01-10T11:47:00');
        $foo = new FooTimestampable();
        $entityManager->persist($foo);
        $entityManager->flush();

        Chronos::setTestNow('2018-01-10T11:48:00');

        $createdAt = $foo->getCreatedAt();
        $updatedAt = $foo->getUpdatedAt();
        $foo->changeId();

        $entityManager->flush();

        self::assertEquals($createdAt, $foo->getCreatedAt());
        self::assertNotEquals($updatedAt, $foo->getUpdatedAt());

        Chronos::setTestNow();
    }
}
