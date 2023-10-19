<?php declare(strict_types=1);

namespace Refugis\DoctrineExtra\Tests\Mock\ORM;

use Doctrine\DBAL\Driver\PDO\MySQL\Driver;
use Doctrine\DBAL\Driver\ServerInfoAwareConnection;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Prophecy\Prophecy\ObjectProphecy;
use Refugis\DoctrineExtra\ORM\EntityRepository;
use Refugis\DoctrineExtra\Tests\Mock\FakeMetadataFactory;
use Refugis\DoctrineExtra\Tests\Mock\Platform;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\DoctrineProvider;

trait EntityManagerTrait
{
    private ?EntityManagerInterface $entityManager = null;
    private Connection $connection;

    /**
     * @var DriverConnection|ObjectProphecy
     */
    private ObjectProphecy $innerConnection;
    private Configuration $configuration;

    public function getEntityManager(): EntityManagerInterface
    {
        if (null !== $this->entityManager) {
            return $this->entityManager;
        }

        $this->configuration = new Configuration();

        if (method_exists($this->configuration, 'setResultCache')) {
            $this->configuration->setResultCache(new ArrayAdapter());
        } else {
            $this->configuration->setResultCacheImpl(new DoctrineProvider(new ArrayAdapter()));
        }

        $this->configuration->setClassMetadataFactoryName(FakeMetadataFactory::class);
        $this->configuration->setMetadataDriverImpl($this->prophesize(MappingDriver::class)->reveal());
        $this->configuration->setProxyDir(\sys_get_temp_dir());
        $this->configuration->setProxyNamespace('__TMP__\\ProxyNamespace\\');
        $this->configuration->setAutoGenerateProxyClasses(AbstractProxyFactory::AUTOGENERATE_ALWAYS);
        $this->configuration->setDefaultRepositoryClassName(EntityRepository::class);

        $this->innerConnection = class_exists(ServerInfoAwareConnection::class) ?
            $this->prophesize(ServerInfoAwareConnection::class) :
            $this->prophesize(DriverConnection::class);

        $this->connection = new Connection([
            'user' => 'user',
            'name' => 'dbname',
            'platform' => new Platform(),
        ], new Driver(), $this->configuration);

        (fn (DriverConnection $connection) => $this->_conn = $connection)
            ->bindTo($this->connection, Connection::class)($this->innerConnection->reveal());

        if (!method_exists(EntityManager::class, 'create')) {
            $this->entityManager = new EntityManager($this->connection, $this->configuration);
        } else {
            $this->entityManager = EntityManager::create($this->connection, $this->configuration);
        }

        return $this->entityManager;
    }
}
