<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\ORM\Metadata;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataFactory as Base;

class ClassMetadataFactory extends Base
{
    private EntityManagerInterface $em;

    public function setEntityManager(EntityManagerInterface $em): void
    {
        $this->em = $em;

        parent::setEntityManager($em);
    }

    protected function newClassMetadataInstance(string $className): ClassMetadata
    {
        return new ClassMetadata($className, $this->em->getConfiguration()->getNamingStrategy());
    }
}
