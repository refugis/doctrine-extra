<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\ODM\MongoDB;

use Doctrine\ODM\MongoDB\LockMode;
use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository as BaseRepository;
use Refugis\DoctrineExtra\ObjectIteratorInterface;
use Refugis\DoctrineExtra\ObjectRepositoryInterface;

use function assert;
use function is_array;
use function is_object;
use function iterator_to_array;

/**
 * @template T of object
 * @extends BaseRepository<T>
 * @implements ObjectRepositoryInterface<T>
 */
class DocumentRepository extends BaseRepository implements ObjectRepositoryInterface
{
    public function all(): ObjectIteratorInterface
    {
        return new DocumentIterator($this->createQueryBuilder());
    }

    /**
     * {@inheritDoc}
     */
    public function count(array $criteria = []): int
    {
        /* @phpstan-ignore-next-line */
        return (int) $this->buildQueryBuilderForCriteria($criteria)
            ->count()
            ->getQuery()
            ->execute();
    }

    /**
     * {@inheritDoc}
     */
    public function findOneByCached(array $criteria, array|null $orderBy = null, int $ttl = 28800): object|null
    {
        $query = $this->buildQueryBuilderForCriteria($criteria, $orderBy);
        $query->limit(1);

        // This is commented due to the missing cache part in doctrine/mongo-odm
        // $query->getQuery()->useResultCache(true, $ttl, '__'.get_called_class().'::'.__FUNCTION__.sha1(serialize(func_get_args())));

        /* @phpstan-ignore-next-line */
        return $query->getQuery()->getSingleResult();
    }

    /**
     * {@inheritDoc}
     */
    public function findByCached(
        array $criteria,
        array|null $orderBy = null,
        int|null $limit = null,
        int|null $offset = null,
        int $ttl = 28800,
    ): array {
        $query = $this->buildQueryBuilderForCriteria($criteria, $orderBy);
        // This is commented due to the missing cache part in doctrine/mongo-odm
        // $query->getQuery()->useResultCache(true, $ttl, '__'.get_called_class().'::'.__FUNCTION__.sha1(serialize(func_get_args())));

        return iterator_to_array($query->getQuery()->getIterator());
    }

    public function get(mixed $id, int|null $lockMode = null, int|null $lockVersion = null): object
    {
        $document = $this->find($id, $lockMode ?? LockMode::NONE, $lockVersion);
        if ($document === null) {
            throw new Exception\NoResultException();
        }

        return $document;
    }

    /**
     * {@inheritDoc}
     */
    public function getOneBy(array $criteria, array|null $orderBy = null): object
    {
        $query = $this->buildQueryBuilderForCriteria($criteria, $orderBy);
        $query->limit(1);

        /** @var T | null $object */ // phpcs:ignore
        $object = $query->getQuery()->getSingleResult();
        if ($object === null) {
            throw new Exception\NoResultException();
        }

        assert(is_object($object));

        return $object;
    }

    /**
     * {@inheritDoc}
     */
    public function getOneByCached(array $criteria, array|null $orderBy = null, int $ttl = 28800): object
    {
        $query = $this->buildQueryBuilderForCriteria($criteria, $orderBy);
        $query->limit(1);
//        $query->getQuery()->useResultCache(true, $ttl, '__'.get_called_class().'::'.__FUNCTION__.sha1(serialize(func_get_args())));

        /** @var T | null $object */ // phpcs:ignore
        $object = $query->getQuery()->getSingleResult();
        if ($object === null) {
            throw new Exception\NoResultException();
        }

        assert(is_object($object));

        return $object;
    }

    /**
     * Builds a query builder for find operations.
     *
     * @param array<string, mixed> $criteria
     * @param array<string, string>|null $orderBy
     */
    private function buildQueryBuilderForCriteria(array $criteria, array|null $orderBy = null): Builder
    {
        $qb = $this->createQueryBuilder();

        foreach ($criteria as $key => $value) {
            $method = is_array($value) ? 'in' : 'equals';
            $qb->field($key)->{$method}($value);
        }

        if ($orderBy !== null) {
            $qb->sort($orderBy);
        }

        return $qb;
    }
}
