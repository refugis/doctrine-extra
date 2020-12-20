<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\ODM\MongoDB;

use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository as BaseRepository;
use Refugis\DoctrineExtra\ObjectIteratorInterface;
use Refugis\DoctrineExtra\ObjectRepositoryInterface;

use function assert;
use function is_array;
use function is_object;
use function iterator_to_array;

class DocumentRepository extends BaseRepository implements ObjectRepositoryInterface
{
    public function all(): ObjectIteratorInterface
    {
        return new DocumentIterator($this->createQueryBuilder());
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function findOneByCached(array $criteria, ?array $orderBy = null, int $ttl = 28800): ?object
    {
        $query = $this->buildQueryBuilderForCriteria($criteria, $orderBy);
        $query->limit(1);

        // This is commented due to the missing cache part in doctrine/mongo-odm
        // $query->getQuery()->useResultCache(true, $ttl, '__'.get_called_class().'::'.__FUNCTION__.sha1(serialize(func_get_args())));

        /* @phpstan-ignore-next-line */
        return $query->getQuery()->getSingleResult();
    }

    /**
     * {@inheritdoc}
     */
    public function findByCached(
        array $criteria,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
        int $ttl = 28800
    ): array {
        $query = $this->buildQueryBuilderForCriteria($criteria, $orderBy);
        // This is commented due to the missing cache part in doctrine/mongo-odm
        // $query->getQuery()->useResultCache(true, $ttl, '__'.get_called_class().'::'.__FUNCTION__.sha1(serialize(func_get_args())));

        return iterator_to_array($query->getQuery()->getIterator());
    }

    /**
     * {@inheritdoc}
     */
    public function get($id, $lockMode = null, $lockVersion = null): object
    {
        $document = $this->find($id, $lockMode, $lockVersion);
        if ($document === null) {
            throw new Exception\NoResultException();
        }

        return $document;
    }

    /**
     * {@inheritdoc}
     */
    public function getOneBy(array $criteria, ?array $orderBy = null): object
    {
        $query = $this->buildQueryBuilderForCriteria($criteria, $orderBy);
        $query->limit(1);

        $object = $query->getQuery()->getSingleResult();
        if ($object === null) {
            throw new Exception\NoResultException();
        }

        assert(is_object($object));

        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function getOneByCached(array $criteria, ?array $orderBy = null, int $ttl = 28800): object
    {
        $query = $this->buildQueryBuilderForCriteria($criteria, $orderBy);
        $query->limit(1);
//        $query->getQuery()->useResultCache(true, $ttl, '__'.get_called_class().'::'.__FUNCTION__.sha1(serialize(func_get_args())));

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
    private function buildQueryBuilderForCriteria(array $criteria, ?array $orderBy = null): Builder
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
