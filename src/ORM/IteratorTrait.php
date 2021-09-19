<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\ORM;

use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\FetchMode;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\QueryBuilder;
use Refugis\DoctrineExtra\IteratorTrait as BaseIteratorTrait;

use function assert;
use function is_string;
use function method_exists;

trait IteratorTrait
{
    use BaseIteratorTrait;

    private QueryBuilder $queryBuilder;
    private ?int $totalCount;

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

                $parser = new Parser($queryBuilder->getQuery());
                $parserResult = $parser->parse();

                $parameters = $queryBuilder->getParameters();
                foreach ($parserResult->getParameterMappings() as $name => $mapping) {
                    $parameter = $parameters->filter(static fn (Parameter $parameter) => $parameter->getName() === $name)->first();
                    if ($parameter === false) {
                        continue;
                    }

                    assert($parameter instanceof Parameter);
                    foreach ($mapping as $position) {
                        $dbalQb->setParameter($position, $parameter->getValue(), $parameter->getType());
                    }
                }

                $sql = $parserResult->getSqlExecutor()->getSqlStatements();
                assert(is_string($sql));

                $dbalQb->select('COUNT(*)')->from('(' . $sql . ') scrl_c_0');
                $stmt = $dbalQb->execute();

                assert($stmt instanceof ResultStatement);

                if (method_exists($stmt, 'fetchOne')) {
                    return (int) $stmt->fetchOne();
                }

                return (int) $stmt->fetch(FetchMode::COLUMN);
            }

            $distinct = $queryBuilder->getDQLPart('distinct') ? 'DISTINCT ' : '';
            $queryBuilder->resetDQLPart('distinct');

            $em = $queryBuilder->getEntityManager();
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
}
