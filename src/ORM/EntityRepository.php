<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\ORM;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository as BaseRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Refugis\DoctrineExtra\ObjectIteratorInterface;
use Refugis\DoctrineExtra\ObjectRepositoryInterface;

use function func_get_args;
use function is_array;
use function serialize;
use function sha1;
use function str_replace;

/**
 * @template T of object
 * @extends BaseRepository<T>
 * @implements ObjectRepositoryInterface<T>
 */
class EntityRepository extends BaseRepository implements ObjectRepositoryInterface
{
    private const INVALID_CACHE_KEY_CHARS = ['{', '}', '(', ')', '/', '\\', '@', ':'];

    public function all(): ObjectIteratorInterface
    {
        return new EntityIterator($this->createQueryBuilder('a'));
    }

    public function count(array|Criteria $criteria = []): int
    {
        return $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($criteria);
    }

    /**
     * {@inheritDoc}
     */
    public function findOneByCached(array $criteria, array|null $orderBy = null, int $ttl = 28800): object|null
    {
        $query = $this->buildQueryForFind($criteria, $orderBy);
        $query->setMaxResults(1);

        $cacheKey = str_replace(self::INVALID_CACHE_KEY_CHARS, '', '__' . static::class . '::' . __FUNCTION__ . sha1(serialize(func_get_args())));
        $query->enableResultCache($ttl, $cacheKey);

        try {
            return $query->getOneOrNullResult();
        } catch (NonUniqueResultException $e) { /* @phpstan-ignore-line */
            throw new Exception\NonUniqueResultException($e->getMessage());
        }
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
    ): iterable {
        $query = $this->buildQueryForFind($criteria, $orderBy);
        if ($limit !== null) {
            $query->setMaxResults($limit);
        }

        if ($offset !== null) {
            $query->setFirstResult($offset);
        }

        $cacheKey = str_replace(self::INVALID_CACHE_KEY_CHARS, '', '__' . static::class . '::' . __FUNCTION__ . sha1(serialize(func_get_args())));
        $query->enableResultCache($ttl, $cacheKey);

        return $query->getResult();
    }

    public function get(mixed $id, int|null $lockMode = null, int|null $lockVersion = null): object
    {
        $entity = $this->find($id, $lockMode, $lockVersion);
        if ($entity === null) {
            throw new Exception\NoResultException();
        }

        return $entity;
    }

    /**
     * {@inheritDoc}
     */
    public function getOneBy(array $criteria, array|null $orderBy = null): object
    {
        $entity = $this->findOneBy($criteria, $orderBy);
        if ($entity === null) {
            throw new Exception\NoResultException();
        }

        return $entity;
    }

    /**
     * {@inheritDoc}
     */
    public function getOneByCached(array $criteria, array|null $orderBy = null, int $ttl = 28800): object
    {
        $query = $this->buildQueryForFind($criteria, $orderBy);
        $query->setMaxResults(1);

        $cacheKey = str_replace(self::INVALID_CACHE_KEY_CHARS, '', '__' . static::class . '::' . __FUNCTION__ . sha1(serialize(func_get_args())));
        $query->enableResultCache($ttl, $cacheKey);

        try {
            return $query->getSingleResult();
        } catch (NonUniqueResultException $e) { /* @phpstan-ignore-line */
            throw new Exception\NonUniqueResultException($e->getMessage());
        } catch (NoResultException) {
            throw new Exception\NoResultException();
        }
    }

    /**
     * Builds a query for find method.
     *
     * @param array<string, mixed> $criteria
     * @param array<string, string>|null $orderBy
     */
    private function buildQueryForFind(array $criteria, array|null $orderBy = null): Query
    {
        return $this->buildQueryBuilderForCriteria($criteria, $orderBy)->getQuery();
    }

    /**
     * Builds a query builder for find operations.
     *
     * @param array<string, mixed> $criteria
     * @param array<string, string>|null $orderBy
     */
    private function buildQueryBuilderForCriteria(array $criteria, array|null $orderBy = null): QueryBuilder
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
