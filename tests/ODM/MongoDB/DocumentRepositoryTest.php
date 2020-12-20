<?php declare(strict_types=1);

namespace Refugis\DoctrineExtra\Tests\ODM\MongoDB;

use Composer\InstalledVersions;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ORM\NonUniqueResultException;
use MongoDB\BSON\ObjectId;
use MongoDB\Model\BSONDocument;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Refugis\DoctrineExtra\ODM\MongoDB\DocumentIterator;
use Refugis\DoctrineExtra\ODM\MongoDB\DocumentRepository;
use Refugis\DoctrineExtra\ODM\MongoDB\Exception\NoResultException;
use Refugis\DoctrineExtra\Tests\Fixtures\Document\MongoDB\FooBar;
use Refugis\DoctrineExtra\Tests\Mock\ODM\MongoDB\DocumentManagerTrait;
use Refugis\DoctrineExtra\Tests\Mock\ODM\MongoDB\Repository;
use MongoDB\BSON\Serializable;

class DocumentRepositoryTest extends TestCase
{
    use DocumentManagerTrait;
    use ProphecyTrait;

    private DocumentRepository $repository;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        if (! \interface_exists(Serializable::class)) {
            self::markTestSkipped('Mongo extension not installed');
        }

        $documentManager = $this->getDocumentManager();
        $class = new ClassMetadata(FooBar::class);
        $documentManager->getMetadataFactory()->setMetadataFor(FooBar::class, $class);

        $class->mapField(['fieldName' => 'id', 'type' => 'id']);
        $class->setIdentifier('id');

        $this->repository = new Repository($documentManager, $documentManager->getUnitOfWork(), $class);
    }

    public function testAllShouldReturnADocumentIterator(): void
    {
        $this->collection->find([], Argument::any())->willReturn(new \ArrayIterator([]));
        self::assertInstanceOf(DocumentIterator::class, $this->repository->all());
    }

    public function testCountWillReturnRowCount(): void
    {
        if (version_compare($this->odmVersion, '2.0.0', '>=')) {
            $this->collection->count([], Argument::any())->willReturn(42);
        } else {
            $this->database->command(new BSONDocument([
                'count' => 'FooBar',
                'query' => new BSONDocument(),
                'limit' => 0,
                'skip' => 0,
            ]), Argument::any())
            ->willReturn(new \ArrayIterator([
                [
                    'n' => 42,
                    'query' => (object)[],
                    'ok' => true,
                ],
            ]));
        }

        self::assertSame(42, $this->repository->count());
    }

    public function testFindOneByCachedShouldCheckCache(): void
    {
        self::markTestSkipped('Mongo ODM does not support result cache');
    }

    public function testFindOneByCachedShouldThrowIdNonUniqueResultHasBeenReturned(): void
    {
        $this->expectException(NonUniqueResultException::class);

        self::markTestSkipped('Mongo ODM does not support result cache');
    }

    public function testFindByCachedShouldCheckCache(): void
    {
        self::markTestSkipped('Mongo ODM does not support result cache');
    }

    public function testFindByCachedShouldFireTheCorrectQuery(): void
    {
        self::markTestSkipped('Mongo ODM does not support result cache');
    }

    public function testGetShouldReturnADocument(): void
    {
        if (version_compare($this->odmVersion, '2.0.0', '>=')) {
            $call = $this->collection
                ->findOne(['_id' => new ObjectId('5a3d346ab7f26e18ba119308')], Argument::any())
                ->willReturn([
                    '_id' => new ObjectId('5a3d346ab7f26e18ba119308'),
                    'id' => '5a3d346ab7f26e18ba119308',
                ]);
        } else {
            $call = $this->collection
                ->find(new BSONDocument(['_id' => '5a3d346ab7f26e18ba119308']), Argument::any())
                ->willReturn(new \ArrayIterator([
                    [
                        '_id' => '5a3d346ab7f26e18ba119308',
                        'id' => '5a3d346ab7f26e18ba119308',
                    ],
                ]));
        }

        $call->shouldBeCalledTimes(1);
        $obj1 = $this->repository->get('5a3d346ab7f26e18ba119308');

        self::assertInstanceOf(FooBar::class, $obj1);
        self::assertEquals('5a3d346ab7f26e18ba119308', $obj1->id);
    }

    public function testGetShouldThrowIfNoResultIsFound(): void
    {
        $this->expectException(NoResultException::class);

        if (version_compare($this->odmVersion, '2.0.0', '>=')) {
            $call = $this->collection
                ->findOne(['_id' => new ObjectId('5a3d346ab7f26e18ba119308')], Argument::any())
                ->willReturn(null);
        } else {
            $call = $this->collection
                ->find(new BSONDocument(['_id' => '5a3d346ab7f26e18ba119308']), Argument::any())
                ->willReturn(new \ArrayIterator([]));
        }

        $call->shouldBeCalledTimes(1);
        $this->repository->get('5a3d346ab7f26e18ba119308');
    }

    public function testGetOneByShouldReturnADocument(): void
    {
        if (version_compare($this->odmVersion, '2.0.0', '>=')) {
            $call = $this->collection->find(['_id' => new ObjectId('5a3d346ab7f26e18ba119308')], Argument::any());
        } else {
            $call = $this->collection->find(new BSONDocument(['_id' => '5a3d346ab7f26e18ba119308']), Argument::any());
        }

        $call
            ->shouldBeCalledTimes(1)
            ->willReturn(new \ArrayIterator([
                [
                    '_id' => '5a3d346ab7f26e18ba119308',
                    'id' => '5a3d346ab7f26e18ba119308',
                ],
            ]));
        $obj1 = $this->repository->getOneBy(['id' => '5a3d346ab7f26e18ba119308']);

        self::assertInstanceOf(FooBar::class, $obj1);
        self::assertEquals('5a3d346ab7f26e18ba119308', $obj1->id);
    }

    public function testGetOneByShouldThrowIfNoResultIsFound(): void
    {
        $this->expectException(NoResultException::class);

        if (version_compare($this->odmVersion, '2.0.0', '>=')) {
            $call = $this->collection->find(['_id' => new ObjectId('5a3d346ab7f26e18ba119308')], Argument::any());
        } else {
            $call = $this->collection->find(new BSONDocument(['_id' => '5a3d346ab7f26e18ba119308']), Argument::any());
        }

        $call
            ->shouldBeCalledTimes(1)
            ->willReturn(new \ArrayIterator([]));

        $this->repository->getOneBy(['id' => '5a3d346ab7f26e18ba119308']);
    }

    public function testGetOneByCachedShouldCheckTheCache(): void
    {
        self::markTestSkipped('Mongo ODM does not support result cache');
    }

    public function testRepositoryIsInstanceOfDocumentRepository(): void
    {
        $class = \get_class($this->documentManager->getRepository(FooBar::class));
        self::assertTrue(DocumentRepository::class === $class || \is_subclass_of($class, DocumentRepository::class));
    }
}
