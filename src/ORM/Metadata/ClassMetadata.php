<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\ORM\Metadata;

use Doctrine\Instantiator\Instantiator;
use Doctrine\ORM\Mapping\ClassMetadata as BaseClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Mapping\ReflectionEmbeddedProperty;
use Doctrine\Persistence\Mapping\ReflectionService;
use ReflectionProperty;

use function assert;
use function class_exists;

class ClassMetadata extends BaseClassMetadata
{
    /**
     * {@inheritdoc}
     */
    public function wakeupReflection($reflectionService): void
    {
        // Restore ReflectionClass and properties
        $reflectionClass = $reflectionService->getClass($this->name);
        assert($reflectionClass !== null);

        $this->reflClass = $reflectionClass;

        $instantiatorProperty = new ReflectionProperty(ClassMetadataInfo::class, 'instantiator');
        $instantiatorProperty->setAccessible(true);

        $instantiator = $instantiatorProperty->getValue($this);
        if (! $instantiator) {
            $instantiatorProperty->setValue($this, new Instantiator());
        }

        $parentReflectionFields = [];

        foreach ($this->embeddedClasses as $property => $embeddedClass) {
            if (isset($embeddedClass['declaredField'])) {
                $class = $this->embeddedClasses[$embeddedClass['declaredField']]['class'] ?? null;
                $parentProperty = $parentReflectionFields[$embeddedClass['declaredField']] ?? null;
                $childProperty = $reflectionService->getAccessibleProperty($class, $embeddedClass['originalField']);

                assert(isset($class, $parentProperty, $childProperty));
                $parentReflectionFields[$property] = new ReflectionEmbeddedProperty($parentProperty, $childProperty, $class);

                continue;
            }

            $fieldReflection = $reflectionService->getAccessibleProperty($embeddedClass['declared'] ?? $this->name, $property);

            $parentReflectionFields[$property] = $fieldReflection;
            $this->reflFields[$property] = $fieldReflection;
        }

        foreach ($this->fieldMappings as $field => $mapping) {
            $this->mapReflectionField($mapping, $parentReflectionFields, $reflectionService, $field);
        }

        foreach ($this->associationMappings as $key => $mapping) {
            $this->mapReflectionField($mapping, $parentReflectionFields, $reflectionService, $key);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function inlineEmbeddable($property, ClassMetadataInfo $embeddable): void
    {
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
                        $this->reflClass->name,
                        $embeddable->reflClass->name
                    );
            }

            $this->mapField($fieldMapping);
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
                            $this->reflClass->name,
                            $embeddable->reflClass->name
                        );
                }
            }

            unset($column);
            $this->mapManyToOne($assocMapping);
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
