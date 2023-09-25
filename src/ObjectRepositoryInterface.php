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
    public function findOneByCached(array $criteria, array|null $orderBy = null, int $ttl = 28800): object|null;

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
        array|null $orderBy = null,
        int|null $limit = null,
        int|null $offset = null,
        int $ttl = 28800,
    ): iterable;

    /**
     * Finds an object by its primary key / identifier.
     * Throws an exception if the object cannot be found.
     *
     * @psalm-return T
     * @phpstan-return T
     *
     * @throws NoResultExceptionInterface
     */
    public function get(mixed $id, int|null $lockMode = null, int|null $lockVersion = null): object;

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
    public function getOneBy(array $criteria, array|null $orderBy = null): object;

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
    public function getOneByCached(array $criteria, array|null $orderBy = null, int $ttl = 28800): object;
}
