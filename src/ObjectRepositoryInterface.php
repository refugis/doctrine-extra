<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra;

use Doctrine\Persistence\ObjectRepository as BaseRepository;
use Refugis\DoctrineExtra\Exception\NonUniqueResultExceptionInterface;
use Refugis\DoctrineExtra\Exception\NoResultExceptionInterface;

/**
 * @template T of object
 * @extends BaseRepository<T>
 */
interface ObjectRepositoryInterface extends BaseRepository
{
    /**
     * Gets an iterator to traverse all the objects of the repository.
     *
     * @psalm-return ObjectIteratorInterface<T>
     * @phpstan-return ObjectIteratorInterface<T>
     */
    public function all(): ObjectIteratorInterface;

    /**
     * Counts entities by a set of criteria.
     *
     * @param array<string, mixed> $criteria
     */
    public function count(array $criteria = []): int;

    /**
     * Finds a single object by a set of criteria and cache the result for next calls.
     *
     * @param array<string, mixed> $criteria
     * @param array<string, string>|null $orderBy
     *
     * @return object|null the entity instance or NULL if the entity can not be found
     * @psalm-return ?T
     * @phpstan-return T | null
     *
     * @throws NonUniqueResultExceptionInterface
     */
    public function findOneByCached(array $criteria, ?array $orderBy = null, int $ttl = 28800): ?object;

    /**
     * Finds objects by a set of criteria and cache the result for next calls.
     *
     * @param array<string, mixed> $criteria
     * @param array<string, string>|null $orderBy
     *
     * @return array<object> The objects
     * @psalm-return T[]
     * @phpstan-return T[]
     */
    public function findByCached(
        array $criteria,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
        int $ttl = 28800
    ): iterable;

    /**
     * Finds an object by its primary key / identifier.
     * Throws an exception if the object cannot be found.
     *
     * @param mixed      $id
     * @param mixed|null $lockMode
     * @param mixed|null $lockVersion
     *
     * @psalm-return T
     * @phpstan-return T
     *
     * @throws NoResultExceptionInterface
     */
    public function get($id, $lockMode = null, $lockVersion = null): object;

    /**
     * Finds a single object by a set of criteria and cache the result for next calls.
     *
     * @param array<string, mixed> $criteria
     * @param array<string, string>|null $orderBy
     *
     * @psalm-return T
     * @phpstan-return T
     *
     * @throws NoResultExceptionInterface
     * @throws NonUniqueResultExceptionInterface
     */
    public function getOneBy(array $criteria, ?array $orderBy = null): object;

    /**
     * Finds a single object by a set of criteria and cache the result for next calls.
     * Throws an exception if the object cannot be found.
     *
     * @param array<string, mixed> $criteria
     * @param array<string, string>|null $orderBy
     *
     * @psalm-return T
     * @phpstan-return T
     *
     * @throws NoResultExceptionInterface
     * @throws NonUniqueResultExceptionInterface
     */
    public function getOneByCached(array $criteria, ?array $orderBy = null, int $ttl = 28800): object;
}
