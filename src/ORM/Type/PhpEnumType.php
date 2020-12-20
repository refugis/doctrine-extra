<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\ORM\Type;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;
use MyCLabs\Enum\Enum;

use function array_map;
use function assert;
use function is_string;
use function is_subclass_of;
use function json_decode;
use function json_encode;

use const JSON_THROW_ON_ERROR;

class PhpEnumType extends Type
{
    protected string $name = 'enum';
    protected string $enumClass = Enum::class;
    protected bool $multiple = false;

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        if ($this->multiple) {
            return $platform->getJsonTypeDeclarationSQL([]);
        }

        return $platform->getVarcharTypeDeclarationSQL([]);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null || $value === '') {
            return null;
        }

        $valueToEnumConverter = function ($enumValue): Enum {
            if (! $this->enumClass::isValid($enumValue)) {
                throw ConversionException::conversionFailed($enumValue, $this->name);
            }

            return new $this->enumClass($enumValue);
        };

        if (! $this->multiple) {
            return $valueToEnumConverter($value);
        }

        return array_map($valueToEnumConverter, json_decode($value, true, 512, JSON_THROW_ON_ERROR));
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        $enumToValueConverter = function (Enum $enum): string {
            if (! $enum instanceof $this->enumClass) {
                throw ConversionException::conversionFailedInvalidType($enum, $this->name, [$this->enumClass]);
            }

            return (string) $enum;
        };

        if (! $this->multiple) {
            return $enumToValueConverter($value);
        }

        return json_encode(array_map($enumToValueConverter, $value), JSON_THROW_ON_ERROR);
    }

    /**
     * @throws Exception
     *
     * @phpstan-param string|class-string<Enum> $typeNameOrEnumClass
     * @phpstan-param class-string<Enum>|null $enumClass
     */
    public static function registerEnumType(string $typeNameOrEnumClass, ?string $enumClass = null): void
    {
        $typeName = $typeNameOrEnumClass;
        $enumClass ??= $typeNameOrEnumClass;

        if (! is_subclass_of($enumClass, Enum::class)) {
            throw new InvalidArgumentException('Provided enum class "' . $enumClass . '" is not valid. Enums must extend "' . Enum::class . '"');
        }

        // Register and customize the type
        self::addType($typeName, static::class);

        $type = self::getType($typeName);
        assert($type instanceof PhpEnumType);

        $type->name = $typeName;
        $type->enumClass = $enumClass;

        $multipleEnumType = 'array<' . $typeName . '>';
        self::addType($multipleEnumType, static::class);

        $type = self::getType($multipleEnumType);
        assert($type instanceof PhpEnumType);

        $type->name = $multipleEnumType;
        $type->enumClass = $enumClass;
        $type->multiple = true;
    }

    /**
     * @param array<string|int, string> $types
     *
     * @phpstan-param array<string|int, class-string<Enum>> $types
     */
    public static function registerEnumTypes(array $types): void
    {
        foreach ($types as $typeName => $enumClass) {
            $typeName = is_string($typeName) ? $typeName : $enumClass;
            static::registerEnumType($typeName, $enumClass);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }
}
