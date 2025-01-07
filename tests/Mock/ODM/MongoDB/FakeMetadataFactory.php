<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\Tests\Mock\ODM\MongoDB;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactoryInterface as MongoDBMetadataFactory;
use Doctrine\Persistence\Mapping\MappingException;
use Doctrine\Persistence\Mapping\ProxyClassNameResolver;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @method ClassMetadata[] getLoadedMetadata()
 */
class FakeMetadataFactory implements MongoDBMetadataFactory
{
    private array $metadata;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        $this->metadata = [];
    }

    public function setDocumentManager($dm): void
    {
    }

    public function setConfiguration(mixed $config): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getAllMetadata(): array
    {
        return \array_values($this->metadata);
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataFor($className): ClassMetadata
    {
        if (! isset($this->metadata[$className])) {
            throw new MappingException('Cannot find metadata for "'.$className.'"');
        }

        return $this->metadata[$className];
    }

    /**
     * {@inheritdoc}
     */
    public function hasMetadataFor($className): bool
    {
        return isset($this->metadata[$className]);
    }

    /**
     * {@inheritdoc}
     */
    public function setMetadataFor($className, $class): void
    {
        $this->metadata[$className] = $class;
    }

    /**
     * {@inheritdoc}
     */
    public function isTransient($className): bool
    {
        return false;
    }

    public function setCache(CacheItemPoolInterface $cache): void
    {
    }

    public function setProxyClassNameResolver(ProxyClassNameResolver $resolver): void
    {
    }
}
