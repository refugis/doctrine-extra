<?php declare(strict_types=1);

namespace Refugis\DoctrineExtra\Tests\ORM;

use Composer\InstalledVersions;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use PDO;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Refugis\DoctrineExtra\DBAL\DummyResult;
use Refugis\DoctrineExtra\DBAL\DummyStatement;
use Refugis\DoctrineExtra\ORM\EntityIterator;
use Refugis\DoctrineExtra\ORM\EntityRepository;
use Refugis\DoctrineExtra\Tests\Fixtures\Entity\FooBar;
use Refugis\DoctrineExtra\Tests\Fixtures\Entity\TestEntity;
use Refugis\DoctrineExtra\Tests\Mock\ORM\EntityManagerTrait;
use Refugis\DoctrineExtra\Tests\Mock\ORM\Repository;

use function get_class;
use function str_replace;

class EntityRepositoryTest extends TestCase
{
    use EntityManagerTrait;
    use ProphecyTrait;

    private const INVALID_CACHE_KEY_CHARS = ['{', '}', '(', ')', '/', '\\', '@', ':'];
    private EntityRepository $repository;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $classMetadata = new ClassMetadata(TestEntity::class);
        $classMetadata->identifier = ['id'];
        $classMetadata->mapField([
            'fieldName' => 'id',
            'type' => 'integer',
            'scale' => null,
            'length' => null,
            'unique' => true,
            'nullable' => false,
            'precision' => null,
        ]);

        $metadataFactory = $this->getEntityManager()->getMetadataFactory();
        $metadataFactory->setMetadataFor(TestEntity::class, $classMetadata);
        $metadataFactory->setMetadataFor(FooBar::class, $classMetadata);

        $this->repository = new Repository($this->getEntityManager(), $classMetadata);
    }

    public function testAllShouldReturnAnEntityIterator(): void
    {
        $this->innerConnection
            ->query('SELECT t0_.id AS id_0 FROM TestEntity t0_')
            ->willReturn(new DummyStatement([]))
        ;

        self::assertInstanceOf(EntityIterator::class, $this->repository->all());
    }

    public function testCountWillReturnRowCount(): void
    {
        $this->innerConnection
            ->query('SELECT COUNT(*) FROM TestEntity t0')
            ->willReturn(new DummyStatement([
                ['sclr_0' => '42'],
            ]))
        ;

        self::assertSame(42, $this->repository->count());
    }

    public function testFindOneByCachedShouldCheckCache(): void
    {
        $this->innerConnection
            ->query('SELECT t0_.id AS id_0 FROM TestEntity t0_ LIMIT 1')
            ->willReturn(new DummyResult([['id_0' => '1']]))
            ->shouldBeCalledTimes(1)
        ;

        $obj1 = $this->repository->findOneByCached([]);
        $this->repository->findOneByCached([]);

        $key = '__'.str_replace(self::INVALID_CACHE_KEY_CHARS, '', get_class($this->repository)).'findOneByCachedf6e6f43434391be8b061460900c36046255187c8';
        $cache = $this->configuration->getResultCache();
        self::assertTrue($cache->getItem($key)->isHit());

        self::assertInstanceOf(TestEntity::class, $obj1);
        self::assertEquals(1, $obj1->id);
    }

    public function testFindOneByCachedShouldThrowIdNonUniqueResultHasBeenReturned(): void
    {
        $this->expectException(NonUniqueResultException::class);

        $this->innerConnection
            ->query('SELECT t0_.id AS id_0 FROM TestEntity t0_ LIMIT 1')
            ->willReturn(new DummyResult([
                ['id_0' => '1'],
                ['id_0' => '2'],
            ]))
            ->shouldBeCalledTimes(1)
        ;

        $this->repository->findOneByCached([]);
    }

    public function testFindByCachedShouldCheckCache(): void
    {
        $this->innerConnection
            ->query('SELECT t0_.id AS id_0 FROM TestEntity t0_')
            ->willReturn(new DummyResult([
                ['id_0' => '1'],
                ['id_0' => '2'],
                ['id_0' => '3'],
            ]))
            ->shouldBeCalledTimes(1)
        ;

        $objs = $this->repository->findByCached([]);
        $this->repository->findByCached([]);

        $key = '__'.str_replace(self::INVALID_CACHE_KEY_CHARS, '', get_class($this->repository)).'findByCachedf6e6f43434391be8b061460900c36046255187c8';
        $cache = $this->configuration->getResultCache();
        self::assertTrue($cache->getItem($key)->isHit());

        self::assertCount(3, $objs);
        self::assertEquals(1, $objs[0]->id);
        self::assertEquals(2, $objs[1]->id);
        self::assertEquals(3, $objs[2]->id);
    }

    public function testFindByCachedShouldFireTheCorrectQuery(): void
    {
        $this->innerConnection
            ->prepare('SELECT t0_.id AS id_0 FROM TestEntity t0_ WHERE t0_.id IN (?, ?) ORDER BY t0_.id ASC LIMIT 2 OFFSET 1')
            ->willReturn($statement = $this->prophesize(Statement::class))
            ->shouldBeCalledTimes(1)
        ;

        $statement->bindValue(1, 2, Argument::any())
            ->will(version_compare(InstalledVersions::getVersion('doctrine/dbal'), '4.0.0', '>=') ? static function () {} : static fn () => true);
        $statement->bindValue(2, 3, Argument::any())
            ->will(version_compare(InstalledVersions::getVersion('doctrine/dbal'), '4.0.0', '>=') ? static function () {} : static fn () => true);
        $statement->execute()->willReturn(new DummyResult([
            ['id_0' => '2'],
            ['id_0' => '3'],
        ]));

        $objs = $this->repository->findByCached(['id' => [2, 3]], ['id' => 'asc'], 2, 1);

        self::assertCount(2, $objs);
        self::assertEquals(2, $objs[0]->id);
        self::assertEquals(3, $objs[1]->id);
    }

    public function testGetShouldReturnAnEntity(): void
    {
        $this->innerConnection
            ->prepare('SELECT t0.id AS id_1 FROM TestEntity t0 WHERE t0.id = ?')
            ->willReturn($statement = $this->prophesize(Statement::class))
            ->shouldBeCalledTimes(1)
        ;

        /* @var Statement|ObjectProphecy $statement */
        $statement->bindValue(1, 1, Argument::any())
            ->will(version_compare(InstalledVersions::getVersion('doctrine/dbal'), '4.0.0', '>=') ? static function () {} : static fn () => true);
        $statement->execute()->willReturn(new DummyResult([['id_1' => '1']]));

        $obj1 = $this->repository->get(1);

        self::assertInstanceOf(TestEntity::class, $obj1);
        self::assertEquals(1, $obj1->id);
    }

    public function testGetShouldThrowIfNoResultIsFound(): void
    {
        $this->expectException(NoResultException::class);

        $this->innerConnection
            ->prepare('SELECT t0.id AS id_1 FROM TestEntity t0 WHERE t0.id = ?')
            ->willReturn($statement = $this->prophesize(Statement::class))
            ->shouldBeCalledTimes(1)
        ;

        /* @var Statement|ObjectProphecy $statement */
        $statement->bindValue(1, 1, Argument::any())
            ->will(version_compare(InstalledVersions::getVersion('doctrine/dbal'), '4.0.0', '>=') ? static function () {} : static fn () => true);
        $statement->execute()->willReturn(new DummyResult([]));

        $this->repository->get(1);
    }

    public function testGetOneByShouldReturnAnEntity(): void
    {
        $this->innerConnection
            ->prepare('SELECT t0.id AS id_1 FROM TestEntity t0 WHERE t0.id = ? LIMIT 1')
            ->willReturn($statement = $this->prophesize(Statement::class))
            ->shouldBeCalledTimes(1)
        ;

        /* @var Statement|ObjectProphecy $statement */
        $statement->bindValue(1, 12, Argument::any())
            ->will(version_compare(InstalledVersions::getVersion('doctrine/dbal'), '4.0.0', '>=') ? static function () {} : static fn () => true);
        $statement->execute()->willReturn(new DummyResult([['id_1' => '12']]));

        $obj1 = $this->repository->getOneBy(['id' => 12]);

        self::assertInstanceOf(TestEntity::class, $obj1);
        self::assertEquals(12, $obj1->id);
    }

    public function testGetOneByShouldThrowIfNoResultIsFound(): void
    {
        $this->expectException(NoResultException::class);

        $this->innerConnection
            ->prepare('SELECT t0.id AS id_1 FROM TestEntity t0 WHERE t0.id = ? LIMIT 1')
            ->willReturn($statement = $this->prophesize(Statement::class))
            ->shouldBeCalledTimes(1)
        ;

        /* @var Statement|ObjectProphecy $statement */
        $statement->bindValue(1, 12, Argument::any())
            ->will(version_compare(InstalledVersions::getVersion('doctrine/dbal'), '4.0.0', '>=') ? static function () {} : static fn () => true);
        $statement->execute()->willReturn(new DummyResult([]));

        $this->repository->getOneBy(['id' => 12]);
    }

    public function testGetOneByCachedShouldCheckTheCache(): void
    {
        $this->innerConnection
            ->prepare('SELECT t0_.id AS id_0 FROM TestEntity t0_ WHERE t0_.id = ? LIMIT 1')
            ->willReturn($statement = $this->prophesize(Statement::class))
            ->shouldBeCalledTimes(1)
        ;

        /* @var Statement|ObjectProphecy $statement */
        $statement->bindValue(1, 12, Argument::any())
            ->will(version_compare(InstalledVersions::getVersion('doctrine/dbal'), '4.0.0', '>=') ? static function () {} : static fn () => true);
        $statement->execute()->willReturn(new DummyResult([['id_0' => '12']]));

        $obj1 = $this->repository->getOneByCached(['id' => 12]);
        $this->repository->getOneByCached(['id' => 12]);

        $key = '__'.str_replace(self::INVALID_CACHE_KEY_CHARS, '', get_class($this->repository)).'getOneByCached48b7e8dc8f3d4c52abba542ba5f3d423da65cf5e';
        $cache = $this->configuration->getResultCache();
        self::assertTrue($cache->getItem($key)->isHit());

        self::assertInstanceOf(TestEntity::class, $obj1);
        self::assertEquals(12, $obj1->id);
    }

    public function testRepositoryIsInstanceOfEntityRepository(): void
    {
        $repositoryClasses = [
            get_class($this->entityManager->getRepository(TestEntity::class)),
            get_class($this->entityManager->getRepository(FooBar::class)),
        ];

        foreach ($repositoryClasses as $class) {
            self::assertTrue(EntityRepository::class === $class || \is_subclass_of($class, EntityRepository::class));
        }
    }
}
