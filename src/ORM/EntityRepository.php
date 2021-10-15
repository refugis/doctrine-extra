<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\ORM;

use Doctrine\ORM\EntityRepository as BaseRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Refugis\DoctrineExtra\ObjectIteratorInterface;
use Refugis\DoctrineExtra\ObjectRepositoryInterface;

use function func_get_args;
use function is_array;
use function method_exists;
use function serialize;
use function sha1;

/**
 * @template T
 * @extends BaseRepository<T>
 */
class EntityRepository extends BaseRepository implements ObjectRepositoryInterface
{
    public function all(): ObjectIteratorInterface
    {
        return new EntityIterator($this->createQueryBuilder('a'));
    }

    /**
     * {@inheritdoc}
     */
    public function count(array $criteria = []): int
    {
        return (int) $this->buildQueryBuilderForCriteria($criteria)
            ->select('COUNT(a)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * {@inheritdoc}
     */
    public function findOneByCached(array $criteria, ?array $orderBy = null, int $ttl = 28800): ?object
    {
        $query = $this->buildQueryForFind($criteria, $orderBy);
        $query->setMaxResults(1);

        $cacheKey = '__' . static::class . '::' . __FUNCTION__ . sha1(serialize(func_get_args()));
        if (method_exists($query, 'enableResultCache')) {
            $query->enableResultCache($ttl, $cacheKey);
        } else {
            $query->useResultCache(true, $ttl, $cacheKey);
        }

        try {
            return $query->getOneOrNullResult();
        } catch (NonUniqueResultException $e) { /* @phpstan-ignore-line */
            throw new Exception\NonUniqueResultException($e->getMessage());
        }
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
    ): iterable {
        $query = $this->buildQueryForFind($criteria, $orderBy);
        if ($limit !== null) {
            $query->setMaxResults($limit);
        }

        if ($offset !== null) {
            $query->setFirstResult($offset);
        }

        $cacheKey = '__' . static::class . '::' . __FUNCTION__ . sha1(serialize(func_get_args()));
        if (method_exists($query, 'enableResultCache')) {
            $query->enableResultCache($ttl, $cacheKey);
        } else {
            $query->useResultCache(true, $ttl, $cacheKey);
        }

        return $query->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function get($id, $lockMode = null, $lockVersion = null): object
    {
        $entity = $this->find($id, $lockMode, $lockVersion);
        if ($entity === null) {
            throw new Exception\NoResultException();
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function getOneBy(array $criteria, ?array $orderBy = null): object
    {
        $entity = $this->findOneBy($criteria, $orderBy);
        if ($entity === null) {
            throw new Exception\NoResultException();
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function getOneByCached(array $criteria, ?array $orderBy = null, int $ttl = 28800): object
    {
        $query = $this->buildQueryForFind($criteria, $orderBy);
        $query->setMaxResults(1);

        $cacheKey = '__' . static::class . '::' . __FUNCTION__ . sha1(serialize(func_get_args()));
        if (method_exists($query, 'enableResultCache')) {
            $query->enableResultCache($ttl, $cacheKey);
        } else {
            $query->useResultCache(true, $ttl, $cacheKey);
        }

        try {
            return $query->getSingleResult();
        } catch (NonUniqueResultException $e) { /* @phpstan-ignore-line */
            throw new Exception\NonUniqueResultException($e->getMessage());
        } catch (NoResultException $e) { /* @phpstan-ignore-line */
            throw new Exception\NoResultException();
        }
    }

    /**
     * Builds a query for find method.
     *
     * @param array<string, mixed> $criteria
     * @param array<string, string>|null $orderBy
     */
    private function buildQueryForFind(array $criteria, ?array $orderBy = null): Query
    {
        return $this->buildQueryBuilderForCriteria($criteria, $orderBy)->getQuery();
    }

    /**
     * Builds a query builder for find operations.
     *
     * @param array<string, mixed> $criteria
     * @param array<string, string>|null $orderBy
     */
    private function buildQueryBuilderForCriteria(array $criteria, ?array $orderBy = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('a');
        $and = $qb->expr()->andX();
        foreach ($criteria as $key => $value) {
            $condition = is_array($value) ?
                $qb->expr()->in('a.' . $key, ':' . $key) :
                $qb->expr()->eq('a.' . $key, ':' . $key);
            $and->add($condition);

            $qb->setParameter($key, $value);
        }

        if ($and->count() > 0) {
            $qb->where($and);
        }

        if ($orderBy !== null) {
            foreach ($orderBy as $fieldName => $orientation) {
                $qb->addOrderBy('a.' . $fieldName, $orientation);
            }
        }

        return $qb;
    }
}
