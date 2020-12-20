<?php declare(strict_types=1);

namespace Refugis\DoctrineExtra\Tests\Mock\ODM\MongoDB;

use Doctrine\MongoDB\Connection;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\SchemaManager;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Database;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Refugis\DoctrineExtra\ODM\MongoDB\DocumentRepository;
use Refugis\DoctrineExtra\Tests\Mock\FakeMetadataFactory;

trait DocumentManagerTrait
{
    private ?DocumentManager $documentManager = null;

    /**
     * @var Client|ObjectProphecy
     */
    private ObjectProphecy $client;

    /**
     * @var Database|ObjectProphecy
     */
    private ObjectProphecy $database;

    /**
     * @var Collection|ObjectProphecy
     */
    private ObjectProphecy $collection;

    private Connection $connection;
    private Configuration $configuration;

    public function getDocumentManager(): DocumentManager
    {
        if (null !== $this->documentManager) {
            return $this->documentManager;
        }

        $server = $this->prophesize(\MongoClient::class);
        $server->getReadPreference()->willReturn(['type' => \MongoClient::RP_PRIMARY]);
        $server->getWriteConcern()->willReturn([
            'w' => 1,
            'wtimeout' => 5000,
        ]);

        $server->getClient()->willReturn($this->client = $this->prophesize(Client::class));

        $this->database = $this->prophesize(Database::class);
        $this->database->withOptions(Argument::any())->willReturn($this->database);
        $this->client->selectDatabase('doctrine', Argument::any())->willReturn($this->database);

        $this->collection = $this->prophesize(Collection::class);
        $this->database->selectCollection('FooBar', Argument::any())->willReturn($this->collection);
        $this->collection->withOptions(Argument::any())->willReturn($this->collection);

        $mongoDb = new \MongoDB($server->reveal(), 'doctrine');
        $server->selectDB('doctrine')->willReturn($mongoDb);
        $server->selectCollection('doctrine', 'FooBar')->willReturn(new \MongoCollection($mongoDb, 'FooBar'));

        $schemaManager = $this->prophesize(SchemaManager::class);
        $this->connection = new Connection($server->reveal());

        $this->configuration = new Configuration();
        $this->configuration->setHydratorDir(\sys_get_temp_dir());
        $this->configuration->setHydratorNamespace('__TMP__\\HydratorNamespace');
        $this->configuration->setProxyDir(\sys_get_temp_dir());
        $this->configuration->setProxyNamespace('__TMP__\\ProxyNamespace');

        if (\method_exists($this->configuration, 'setDefaultDocumentRepositoryClassName')) {
            $this->configuration->setDefaultDocumentRepositoryClassName(DocumentRepository::class);
        } else {
            $this->configuration->setDefaultRepositoryClassName(DocumentRepository::class);
        }

        $this->documentManager = DocumentManager::create($this->connection, $this->configuration);

        (function () use ($schemaManager): void {
            $this->schemaManager = $schemaManager->reveal();
            $this->metadataFactory = new FakeMetadataFactory();
        })->call($this->documentManager);

        return $this->documentManager;
    }
}
