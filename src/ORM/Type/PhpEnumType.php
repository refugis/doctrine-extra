<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\ORM\Type;

use BackedEnum;
use Closure;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\Type;
use IntBackedEnum;
use InvalidArgumentException;
use MyCLabs\Enum\Enum;
use ReflectionEnum;
use ReflectionException;
use UnitEnum;

use function array_map;
use function assert;
use function class_exists;
use function interface_exists;
use function is_a;
use function is_string;
use function is_subclass_of;
use function json_decode;
use function json_encode;
use function method_exists;
use function sprintf;

use const JSON_THROW_ON_ERROR;
use const PHP_VERSION_ID;

class PhpEnumType extends Type
{
    private const TYPE_STRING = 0;
    private const TYPE_INT = 1;

    protected string $name = 'enum';
    protected string $enumClass = Enum::class;
    protected bool $multiple = false;
    private int $type = self::TYPE_STRING;
    private Closure $toPhp;
    private Closure $toDatabase;

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        if ($this->multiple) {
            return $platform->getJsonTypeDeclarationSQL([]);
        }

        if ($this->type === self::TYPE_STRING) {
            return method_exists($platform, 'getStringTypeDeclarationSQL') ?
                $platform->getStringTypeDeclarationSQL([]) :
                /** @phpstan-ignore-next-line */
                $platform->getVarcharTypeDeclarationSQL([]);
        }

        return $platform->getIntegerTypeDeclarationSQL([]);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! $this->multiple) {
            return ($this->toPhp)($value);
        }

        return array_map($this->toPhp, json_decode($value, true, 512, JSON_THROW_ON_ERROR));
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): mixed
    {
        if ($value === null) {
            return null;
        }

        if (! $this->multiple) {
            return ($this->toDatabase)($value);
        }

        return json_encode(array_map($this->toDatabase, $value), JSON_THROW_ON_ERROR);
    }

    /**
     * @phpstan-param string|class-string<Enum> $typeNameOrEnumClass
     * @phpstan-param class-string<Enum>|null $enumClass
     *
     * @throws Exception
     */
    public static function registerEnumType(string $typeNameOrEnumClass, string|null $enumClass = null): void
    {
        $typeName = $typeNameOrEnumClass;
        $enumClass ??= $typeNameOrEnumClass;

        if (! is_subclass_of($enumClass, Enum::class) && ! (interface_exists(UnitEnum::class) && is_a($enumClass, UnitEnum::class, true))) {
            $message = sprintf(
                'Provided enum class "%s" is not valid. %sxtend %s class',
                $enumClass,
                PHP_VERSION_ID >= 80100 ? 'Use a native enum or e' : 'E',
                Enum::class,
            );

            throw new InvalidArgumentException($message);
        }

        $enumType = self::TYPE_STRING;
        // phpcs:disable Squiz.Scope.StaticThisUsage.Found
        if (PHP_VERSION_ID >= 80100 && is_a($enumClass, UnitEnum::class, true)) {
            /* @phpstan-ignore-next-line */
            if (is_a($enumClass, IntBackedEnum::class, true)) {
                $enumType = self::TYPE_INT;
            }

            if (is_a($enumClass, BackedEnum::class, true)) {
                $toPhp = function ($enumValue) use ($enumClass): BackedEnum {
                    $val = $enumClass::tryFrom($enumValue);
                    if ($val === null) {
                        if (class_exists(ValueNotConvertible::class)) {
                            /** @phpstan-ignore-next-line */
                            throw ValueNotConvertible::new($enumValue, $this->name);
                        }

                        /** @phpstan-ignore-next-line */
                        throw ConversionException::conversionFailed($enumValue, $this->name);
                    }

                    return $val;
                };

                $toDatabase = function (BackedEnum $enum) {
                    if (! $enum instanceof $this->enumClass) { /* @phpstan-ignore-line */
                        if (class_exists(InvalidType::class)) {
                            /** @phpstan-ignore-next-line */
                            throw InvalidType::new($enum, $this->name, [$this->enumClass]);
                        }

                        /** @phpstan-ignore-next-line */
                        throw ConversionException::conversionFailedInvalidType($enum, $this->name, [$this->enumClass]);
                    }

                    return $enum->value;
                };
            } else {
                $toPhp = function ($enumValue) use ($enumClass): UnitEnum {
                    $reflection = new ReflectionEnum($enumClass);
                    try {
                        $case = $reflection->getCase($enumValue);
                    } catch (ReflectionException $e) {
                        if (class_exists(ValueNotConvertible::class)) {
                            /** @phpstan-ignore-next-line */
                            throw ValueNotConvertible::new($enumValue, $this->name, previous: $e);
                        }

                        /** @phpstan-ignore-next-line */
                        throw ConversionException::conversionFailed($enumValue, $this->name, $e);
                    }

                    return $case->getValue();
                };

                $toDatabase = function (UnitEnum $enum): string {
                    if (! $enum instanceof $this->enumClass) { /* @phpstan-ignore-line */
                        if (class_exists(InvalidType::class)) {
                            /** @phpstan-ignore-next-line */
                            throw InvalidType::new($enum, $this->name, [$this->enumClass]);
                        }

                        /** @phpstan-ignore-next-line */
                        throw ConversionException::conversionFailedInvalidType($enum, $this->name, [$this->enumClass]);
                    }

                    return $enum->name;
                };
            }
        } else {
            $toPhp = function ($enumValue): Enum {
                if (! $this->enumClass::isValid($enumValue)) { /* @phpstan-ignore-line */
                    if (class_exists(ValueNotConvertible::class)) {
                        /** @phpstan-ignore-next-line */
                        throw ValueNotConvertible::new($enumValue, $this->name);
                    }

                    /** @phpstan-ignore-next-line */
                    throw ConversionException::conversionFailed($enumValue, $this->name);
                }

                return new $this->enumClass($enumValue); /* @phpstan-ignore-line */
            };

            $toDatabase = function (Enum $enum): string {
                if (! $enum instanceof $this->enumClass) { /* @phpstan-ignore-line */
                    if (class_exists(InvalidType::class)) {
                        /** @phpstan-ignore-next-line */
                        throw InvalidType::new($enum, $this->name, [$this->enumClass]);
                    }

                    /** @phpstan-ignore-next-line */
                    throw ConversionException::conversionFailedInvalidType($enum, $this->name, [$this->enumClass]);
                }

                return (string) $enum;
            };
        }

        // phpcs:enable Squiz.Scope.StaticThisUsage.Found

        // Register and customize the type
        self::addType($typeName, static::class);

        $type = self::getType($typeName);
        assert($type instanceof PhpEnumType);

        $type->name = $typeName;
        $type->enumClass = $enumClass;
        $type->type = $enumType;
        $type->toPhp = $toPhp->bindTo($type, self::class);
        $type->toDatabase = $toDatabase->bindTo($type, self::class);

        $multipleEnumType = 'array<' . $typeName . '>';
        self::addType($multipleEnumType, static::class);

        $type = self::getType($multipleEnumType);
        assert($type instanceof PhpEnumType);

        $type->name = $multipleEnumType;
        $type->enumClass = $enumClass;
        $type->type = $enumType;
        $type->toPhp = $toPhp->bindTo($type, self::class);
        $type->toDatabase = $toDatabase->bindTo($type, self::class);
        $type->multiple = true;
    }

    /**
     * @param array<string|int, string> $types
     * @phpstan-param array<string|int, class-string<Enum>> $types
     */
    public static function registerEnumTypes(array $types): void
    {
        foreach ($types as $typeName => $enumClass) {
            $typeName = is_string($typeName) ? $typeName : $enumClass;
            static::registerEnumType($typeName, $enumClass);
        }
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
