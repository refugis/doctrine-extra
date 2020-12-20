<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\ODM\PhpCr;

use Doctrine\ODM\PHPCR\DocumentRepository as BaseRepository;
use Doctrine\ODM\PHPCR\Exception\RuntimeException;
use Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder;
use Doctrine\ODM\PHPCR\Query\Query;
use PHPCR\Query\QueryResultInterface;
use Refugis\DoctrineExtra\ObjectIteratorInterface;
use Refugis\DoctrineExtra\ObjectRepositoryInterface;

use function assert;
use function count;
use function is_array;
use function iterator_to_array;
use function Safe\sprintf;
use function strtolower;

class DocumentRepository extends BaseRepository implements ObjectRepositoryInterface
{
    public function all(): ObjectIteratorInterface
    {
        return new DocumentIterator($this->createQueryBuilder('a'));
    }

    /**
     * {@inheritdoc}
     */
    public function count(array $criteria = []): int
    {
        $result = $this->buildQueryBuilderForCriteria($criteria)
             ->getQuery()
             ->getResult(Query::HYDRATE_PHPCR);

        /* @phpstan-ignore-next-line */
        assert($result instanceof QueryResultInterface);

        return count(iterator_to_array($result->getRows(), false));
    }

    /**
     * {@inheritdoc}
     */
    public function find($id): ?object
    {
        return parent::find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function findOneByCached(array $criteria, ?array $orderBy = null, int $ttl = 28800): ?object
    {
        $query = $this->buildQueryBuilderForCriteria($criteria, $orderBy);
        $query->setMaxResults(1);

        // This is commented due to the missing cache part in doctrine/phpcr-odm
        // $query->getQuery()->useResultCache(true, $ttl, '__'.get_called_class().'::'.__FUNCTION__.sha1(serialize(func_get_args())));

        return $query->getQuery()->getOneOrNullResult();
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
        $query = $this->buildQueryBuilderForCriteria($criteria, $orderBy);

        // This is commented due to the missing cache part in doctrine/phpcr-odm
        // $query->getQuery()->useResultCache(true, $ttl, '__'.get_called_class().'::'.__FUNCTION__.sha1(serialize(func_get_args())));

        return $query->getQuery()->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function get($id, $lockMode = null, $lockVersion = null): object
    {
        $document = $this->find($id);
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
        $query->setMaxResults(1);

        $object = $query->getQuery()->getOneOrNullResult();

        if ($object === null) {
            throw new Exception\NoResultException();
        }

        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function getOneByCached(array $criteria, ?array $orderBy = null, int $ttl = 28800): object
    {
        $query = $this->buildQueryBuilderForCriteria($criteria, $orderBy);
        $query->setMaxResults(1);
//        $query->getQuery()->useResultCache(true, $ttl, '__'.get_called_class().'::'.__FUNCTION__.sha1(serialize(func_get_args())));

        $object = $query->getQuery()->getOneOrNullResult();

        if ($object === null) {
            throw new Exception\NoResultException();
        }

        return $object;
    }

    /**
     * Builds a query builder for find operations.
     *
     * @param array<string, mixed> $criteria
     * @param array<string, string> $orderBy
     */
    private function buildQueryBuilderForCriteria(array $criteria, ?array $orderBy = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('a');
        if (count($criteria) === 0) {
            return $qb;
        }

        $whereOp = $qb->where();
        foreach ($criteria as $key => $value) {
            if (is_array($value)) {
                $orOp = $whereOp->orX();
                foreach ($value as $v) {
                    /* @phpstan-ignore-next-line */
                    $orOp->eq()->field('a.' . $key)->literal($v);
                }
            } else {
                /* @phpstan-ignore-next-line */
                $whereOp->eq()->field('a.' . $key)->literal($value);
            }
        }

        if ($orderBy !== null) {
            $orderOp = $qb->orderBy();
            foreach ($orderBy as $field => $direction) {
                switch (strtolower($direction)) {
                    case 'asc':
                    case '1':
                        $orderOp->asc()->field('a.' . $field);
                        break;

                    case 'desc':
                    case '-1':
                        $orderOp->desc()->field('a.' . $field);
                        break;

                    default:
                        throw new RuntimeException(sprintf('Unknown order direction "%s". Must be one of "asc", "desc", "1" or "-1".', $direction));
                }
            }
        }

        return $qb;
    }
}
