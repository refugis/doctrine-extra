<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\ORM\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\Type;
use Money\Currency;

use function class_exists;
use function method_exists;

class MoneyCurrencyType extends Type
{
    public const NAME = 'money_currency';

    /**
     * {@inheritDoc}
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        if (method_exists($platform, 'getStringTypeDeclarationSQL')) {
            return $platform->getStringTypeDeclarationSQL($column);
        }

        /** @phpstan-ignore-next-line */
        return $platform->getVarcharTypeDeclarationSQL($column);
    }

    /**
     * {@inheritDoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): string|null
    {
        if ($value === null) {
            return null;
        }

        if (! $value instanceof Currency) {
            if (class_exists(InvalidType::class)) {
                throw InvalidType::new($value, self::NAME, ['ISO currency string']);
            }

            /** @phpstan-ignore-next-line */
            throw ConversionException::conversionFailedInvalidType($value, self::NAME, ['ISO currency string']);
        }

        return $value->getCode();
    }

    /**
     * {@inheritDoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): Currency|null
    {
        if ($value === null) {
            return null;
        }

        return new Currency($value);
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
