<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\ORM;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectManager;
use Refugis\DoctrineExtra\IteratorTrait as BaseIteratorTrait;

use function assert;
use function is_string;
use function ksort;
use function method_exists;

trait IteratorTrait
{
    use BaseIteratorTrait;

    private QueryBuilder $queryBuilder;
    private int|null $totalCount;

    public function count(): int
    {
        if ($this->totalCount === null) {
            $queryBuilder = clone $this->queryBuilder;
            $alias = $queryBuilder->getRootAliases()[0];

            $queryBuilder->setFirstResult(null);
            $queryBuilder->setMaxResults(null);

            $queryBuilder->resetDQLPart('orderBy');

            $groupBy = $queryBuilder->getDQLPart('groupBy');
            if (! empty($groupBy)) {
                $dbalQb = $queryBuilder->getEntityManager()->getConnection()->createQueryBuilder();

                $query = $queryBuilder->getQuery();
                $parser = new Parser($query);
                $parserResult = $parser->parse();

                $parameters = $queryBuilder->getParameters();
                $params = [];
                /** @var array<array-key, ArrayParameterType|ParameterType|Type|string> $paramTypes */
                $paramTypes = [];
                foreach ($parserResult->getParameterMappings() as $name => $mapping) {
                    $parameter = $parameters->filter(static fn (Parameter $parameter) => $parameter->getName() === $name)->first();
                    if ($parameter === false) {
                        continue;
                    }

                    assert($parameter instanceof Parameter);
                    foreach ($mapping as $position) {
                        $params[(string) $position] = $parameter->getValue();
                        $paramTypes[(string) $position] = $parameter->getType();
                    }
                }

                ksort($params);
                ksort($paramTypes);

                $dbalQb->setParameters($params, $paramTypes);

                if (method_exists($parserResult, 'prepareSqlExecutor')) {
                    $sqlExecutor = $parserResult->prepareSqlExecutor($query);
                } else {
                    $sqlExecutor = $parserResult->getSqlExecutor();
                }

                $sql = $sqlExecutor->getSqlStatements();
                assert(is_string($sql));

                $dbalQb->select('COUNT(*)')->from('(' . $sql . ') scrl_c_0');
                $result = $dbalQb->executeQuery();

                return (int) $result->fetchOne();
            }

            $distinct = $queryBuilder->getDQLPart('distinct') ? 'DISTINCT ' : '';
            $queryBuilder->resetDQLPart('distinct');

            $em = $queryBuilder->getEntityManager();
            /* @phpstan-ignore-next-line */
            $metadata = $em->getClassMetadata($queryBuilder->getRootEntities()[0]);

            if ($metadata->containsForeignIdentifier) {
                $alias = 'IDENTITY(' . $alias . ')';
            }

            $this->totalCount = (int) $queryBuilder->select('COUNT(' . $distinct . $alias . ')')
                ->getQuery()
                ->getSingleScalarResult();
        }

        return $this->totalCount;
    }

    public function getObjectManager(): ObjectManager
    {
        return $this->queryBuilder->getEntityManager();
    }
}
