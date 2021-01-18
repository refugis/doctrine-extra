<?php declare(strict_types=1);

namespace Refugis\DoctrineExtra\Tests\Mock\ODM\MongoDB;

use Composer\InstalledVersions;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
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

    private Configuration $configuration;
    private string $odmVersion;

    public function getDocumentManager(): DocumentManager
    {
        if (null !== $this->documentManager) {
            return $this->documentManager;
        }

        $this->client = $this->prophesize(Client::class);
        $this->odmVersion = InstalledVersions::getVersion('doctrine/mongodb-odm');

        $this->database = $this->prophesize(Database::class);
        $this->database->withOptions(Argument::any())->willReturn($this->database);
        $this->client->selectDatabase('doctrine', Argument::any())->willReturn($this->database);

        $this->collection = $this->prophesize(Collection::class);
        $this->database->selectCollection('FooBar', Argument::any())->willReturn($this->collection);
        $this->collection->withOptions(Argument::any())->willReturn($this->collection);

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

        $this->client->getTypeMap()->willReturn(['root' => 'array', 'document' => 'array']);
        $this->documentManager = DocumentManager::create($this->client->reveal(), $this->configuration);

        (function (): void {
            $this->metadataFactory = new FakeMetadataFactory();
        })->call($this->documentManager);

        return $this->documentManager;
    }
}
