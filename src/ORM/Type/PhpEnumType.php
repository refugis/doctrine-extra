<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\ORM\Type;

use BackedEnum;
use Closure;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use IntBackedEnum;
use InvalidArgumentException;
use MyCLabs\Enum\Enum;
use ReflectionEnum;
use ReflectionException;
use UnitEnum;

use function array_map;
use function assert;
use function interface_exists;
use function is_a;
use function is_string;
use function is_subclass_of;
use function json_decode;
use function json_encode;
use function Safe\sprintf;

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
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        if ($this->multiple) {
            return $platform->getJsonTypeDeclarationSQL([]);
        }

        return $this->type === self::TYPE_STRING ? $platform->getVarcharTypeDeclarationSQL([]) : $platform->getIntegerTypeDeclarationSQL([]);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! $this->multiple) {
            return ($this->toPhp)($value);
        }

        return array_map($this->toPhp, json_decode($value, true, 512, JSON_THROW_ON_ERROR));
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
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
    public static function registerEnumType(string $typeNameOrEnumClass, ?string $enumClass = null): void
    {
        $typeName = $typeNameOrEnumClass;
        $enumClass ??= $typeNameOrEnumClass;

        if (! is_subclass_of($enumClass, Enum::class) && ! (interface_exists(UnitEnum::class) && is_a($enumClass, UnitEnum::class, true))) {
            $message = sprintf(
                'Provided enum class "%s" is not valid. %sxtend %s class',
                $enumClass,
                PHP_VERSION_ID >= 80100 ? 'Use a native enum or e' : 'E',
                Enum::class
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
                        throw ConversionException::conversionFailed($enumValue, $this->name); /* @phpstan-ignore-line */
                    }

                    return $val;
                };

                $toDatabase = function (BackedEnum $enum) {
                    if (! $enum instanceof $this->enumClass) { /* @phpstan-ignore-line */
                        throw ConversionException::conversionFailedInvalidType($enum, $this->name, [$this->enumClass]); /* @phpstan-ignore-line */
                    }

                    return $enum->value;
                };
            } else {
                $toPhp = function ($enumValue) use ($enumClass): UnitEnum {
                    $reflection = new ReflectionEnum($enumClass);
                    try {
                        $case = $reflection->getCase($enumValue);
                    } catch (ReflectionException $e) {
                        throw ConversionException::conversionFailed($enumValue, $this->name, $e); /* @phpstan-ignore-line */
                    }

                    return $case->getValue();
                };

                $toDatabase = function (UnitEnum $enum): string {
                    if (! $enum instanceof $this->enumClass) { /* @phpstan-ignore-line */
                        throw ConversionException::conversionFailedInvalidType($enum, $this->name, [$this->enumClass]); /* @phpstan-ignore-line */
                    }

                    return $enum->name;
                };
            }
        } else {
            $toPhp = function ($enumValue): Enum {
                if (! $this->enumClass::isValid($enumValue)) { /* @phpstan-ignore-line */
                    throw ConversionException::conversionFailed($enumValue, $this->name); /* @phpstan-ignore-line */
                }

                return new $this->enumClass($enumValue); /* @phpstan-ignore-line */
            };

            $toDatabase = function (Enum $enum): string {
                if (! $enum instanceof $this->enumClass) { /* @phpstan-ignore-line */
                    throw ConversionException::conversionFailedInvalidType($enum, $this->name, [$this->enumClass]); /* @phpstan-ignore-line */
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
        $type->toPhp = $toPhp->bindTo($type, self::class); /* @phpstan-ignore-line */
        $type->toDatabase = $toDatabase->bindTo($type, self::class); /* @phpstan-ignore-line */

        $multipleEnumType = 'array<' . $typeName . '>';
        self::addType($multipleEnumType, static::class);

        $type = self::getType($multipleEnumType);
        assert($type instanceof PhpEnumType);

        $type->name = $multipleEnumType;
        $type->enumClass = $enumClass;
        $type->type = $enumType;
        $type->toPhp = $toPhp->bindTo($type, self::class); /* @phpstan-ignore-line */
        $type->toDatabase = $toDatabase->bindTo($type, self::class); /* @phpstan-ignore-line */
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

    /**
     * {@inheritdoc}
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }
}
