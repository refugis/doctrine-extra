<?php declare(strict_types=1);

namespace Refugis\DoctrineExtra\ORM;

use Doctrine\DBAL\FetchMode;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\QueryBuilder;
use Refugis\DoctrineExtra\IteratorTrait as BaseIteratorTrait;

trait IteratorTrait
{
    use BaseIteratorTrait;

    private QueryBuilder $queryBuilder;

    private ?int $totalCount;

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        if (null === $this->totalCount) {
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
                    if (false === $parameter) {
                        continue;
                    }

                    assert($parameter instanceof Parameter);
                    foreach ($mapping as $position) {
                        $dbalQb->setParameter($position, $parameter->getValue(), $parameter->getType());
                    }
                }

                $dbalQb->select('COUNT(*)')->from('('.$parserResult->getSqlExecutor()->getSqlStatements().') scrl_c_0');

                return (int) $dbalQb->execute()->fetch(FetchMode::COLUMN);
            }

            $distinct = $queryBuilder->getDQLPart('distinct') ? 'DISTINCT ' : '';
            $queryBuilder->resetDQLPart('distinct');

            $em = $queryBuilder->getEntityManager();
            $metadata = $em->getClassMetadata($queryBuilder->getRootEntities()[0]);

            if ($metadata->containsForeignIdentifier) {
                $alias = 'IDENTITY('.$alias.')';
            }

            $this->totalCount = (int) $queryBuilder->select('COUNT('.$distinct.$alias.')')
                ->getQuery()
                ->getSingleScalarResult()
            ;
        }

        return $this->totalCount;
    }
}
