<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\ORM\Metadata;

use Doctrine\Instantiator\Instantiator;
use Doctrine\ORM\Mapping\ClassMetadata as BaseClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Mapping\ReflectionEmbeddedProperty;
use Doctrine\Persistence\Mapping\ReflectionService;
use ReflectionProperty;

use function assert;
use function class_exists;

class ClassMetadata extends BaseClassMetadata
{
    public function wakeupReflection(ReflectionService $reflService): void
    {
        // Restore ReflectionClass and properties
        $reflectionClass = $reflService->getClass($this->name);
        assert($reflectionClass !== null);

        $this->reflClass = $reflectionClass;

        $instantiatorProperty = new ReflectionProperty(BaseClassMetadata::class, 'instantiator');
        $instantiatorProperty->setAccessible(true);

        $instantiator = $instantiatorProperty->getValue($this);
        if (! $instantiator) {
            $instantiatorProperty->setValue($this, new Instantiator());
        }

        $parentReflectionFields = [];

        foreach ($this->embeddedClasses as $property => $embeddedClass) {
            if (isset($embeddedClass['declaredField'])) {
                $class = $this->embeddedClasses[$embeddedClass['declaredField']]['class'] ?? null;
                assert(isset($class, $embeddedClass['originalField']));

                $parentProperty = $parentReflectionFields[$embeddedClass['declaredField']] ?? null;
                $childProperty = $reflService->getAccessibleProperty($class, $embeddedClass['originalField']);
                assert(isset($parentProperty, $childProperty));

                $parentReflectionFields[$property] = new ReflectionEmbeddedProperty($parentProperty, $childProperty, $class);

                continue;
            }

            $fieldReflection = $reflService->getAccessibleProperty($embeddedClass['declared'] ?? $this->name, $property);

            $parentReflectionFields[$property] = $fieldReflection;
            $this->reflFields[$property] = $fieldReflection;
        }

        foreach ($this->fieldMappings as $field => $mapping) {
            $this->mapReflectionField((array) $mapping, $parentReflectionFields, $reflService, $field);
        }

        foreach ($this->associationMappings as $key => $mapping) {
            $this->mapReflectionField((array) $mapping, $parentReflectionFields, $reflService, $key);
        }
    }

    public function inlineEmbeddable(string $property, BaseClassMetadata $embeddable): void
    {
        $reflectionClass = $this->reflClass;

        assert($reflectionClass !== null);
        assert($embeddable->reflClass !== null);

        foreach ($embeddable->fieldMappings as $fieldMapping) {
            $fieldMapping['originalClass'] ??= $embeddable->name;
            $fieldMapping['declaredField'] = isset($fieldMapping['declaredField']) ? $property . '.' . $fieldMapping['declaredField'] : $property;
            $fieldMapping['originalField'] ??= $fieldMapping['fieldName'];
            $fieldMapping['fieldName'] = $property . '.' . $fieldMapping['fieldName'];

            assert(isset($fieldMapping['columnName']));
            if (! empty($this->embeddedClasses[$property]['columnPrefix'])) {
                $fieldMapping['columnName'] = $this->embeddedClasses[$property]['columnPrefix'] . $fieldMapping['columnName'];
            } elseif ($this->embeddedClasses[$property]['columnPrefix'] !== false) {
                $fieldMapping['columnName'] = $this->namingStrategy
                    ->embeddedFieldToColumnName(
                        $property,
                        $fieldMapping['columnName'],
                        $reflectionClass->name,
                        $embeddable->reflClass->name,
                    );
            }

            $this->mapField((array) $fieldMapping);
        }

        foreach ($embeddable->associationMappings as $assocMapping) {
            if (! ($assocMapping['type'] & BaseClassMetadata::MANY_TO_ONE)) {
                continue;
            }

            $assocMapping['originalClass'] ??= $embeddable->name;
            $assocMapping['declaredField'] = isset($assocMapping['declaredField']) ? $property . '_' . $assocMapping['declaredField'] : $property;
            $assocMapping['originalField'] ??= $assocMapping['fieldName'];
            $assocMapping['fieldName'] = $property . '_' . $assocMapping['fieldName'];

            $assocMapping['sourceToTargetKeyColumns'] = [];
            $assocMapping['joinColumnFieldNames'] = [];
            $assocMapping['targetToSourceKeyColumns'] = [];

            foreach ($assocMapping['joinColumns'] as &$column) {
                if (! empty($this->embeddedClasses[$property]['columnPrefix'])) {
                    $column['name'] = $this->embeddedClasses[$property]['columnPrefix'] . $column['name'];
                } elseif ($this->embeddedClasses[$property]['columnPrefix'] !== false) {
                    $column['name'] = $this->namingStrategy
                        ->embeddedFieldToColumnName(
                            $property,
                            $column['name'],
                            $reflectionClass->name,
                            $embeddable->reflClass->name,
                        );
                }
            }

            unset($column);
            $this->mapManyToOne((array) $assocMapping);
        }
    }

    public function validateAssociations(): void
    {
        foreach ($this->associationMappings as $mapping) {
            if (! class_exists($mapping['targetEntity'])) {
                throw MappingException::invalidTargetEntityClass($mapping['targetEntity'], $this->name, $mapping['fieldName']);
            }
        }
    }

    /**
     * @param array<string, mixed> $mapping
     * @param array<string, ReflectionProperty> $parentReflectionFields
     */
    private function mapReflectionField(array $mapping, array &$parentReflectionFields, ReflectionService $reflectionService, string $field): void
    {
        if (isset($mapping['declaredField'], $parentReflectionFields[$mapping['declaredField']])) {
            $originalClass = $mapping['originalClass'];
            $parentProperty = $parentReflectionFields[$mapping['declaredField']];
            $childProperty = $reflectionService->getAccessibleProperty($originalClass, $mapping['originalField']);
            assert(isset($childProperty));

            $this->reflFields[$field] = new ReflectionEmbeddedProperty($parentProperty, $childProperty, $originalClass);

            return;
        }

        $this->reflFields[$field] = $reflectionService->getAccessibleProperty($mapping['declared'] ?? $this->name, $field);
    }
}
