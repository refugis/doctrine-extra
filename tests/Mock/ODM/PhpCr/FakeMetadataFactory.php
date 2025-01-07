<?php declare(strict_types=1);

namespace Refugis\DoctrineExtra\Tests\Mock\ODM\PhpCr;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadataFactory;
use Doctrine\Persistence\Mapping\MappingException;
use Doctrine\Persistence\Mapping\ProxyClassNameResolver;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @method ClassMetadata[] getLoadedMetadata()
 */
class FakeMetadataFactory extends ClassMetadataFactory
{
    private array $metadata;
    private RuntimeReflectionService $reflectionService;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        $this->metadata = [];
        $this->reflectionService = new RuntimeReflectionService();
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

        if ($class instanceof ClassMetadata) {
            $class->initializeReflection($this->reflectionService);
            $class->wakeupReflection($this->reflectionService);
        }
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

    public function __call(string $name, array $arguments)
    {
    }
}
