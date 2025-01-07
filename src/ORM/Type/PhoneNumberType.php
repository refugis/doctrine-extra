<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\ORM\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\Type;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

use function class_exists;
use function method_exists;

class PhoneNumberType extends Type
{
    public const NAME = 'phone_number';

    /**
     * {@inheritDoc}
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        if (method_exists($platform, 'getStringTypeDeclarationSQL')) {
            return $platform->getStringTypeDeclarationSQL($column);
        }

        /** @phpstan-ignore-next-line */
        return $platform->getVarcharTypeDeclarationSQL(['length' => 36]);
    }

    /**
     * {@inheritDoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): string|null
    {
        if ($value === null) {
            return null;
        }

        if (! $value instanceof PhoneNumber) {
            if (class_exists(InvalidType::class)) {
                throw InvalidType::new($value, self::NAME, [PhoneNumber::class]);
            }

            /** @phpstan-ignore-next-line */
            throw ConversionException::conversionFailedInvalidType($value, self::NAME, [PhoneNumber::class]);
        }

        return PhoneNumberUtil::getInstance()->format($value, PhoneNumberFormat::E164);
    }

    /**
     * {@inheritDoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): PhoneNumber|null
    {
        if ($value === null || $value instanceof PhoneNumber) {
            return $value;
        }

        $util = PhoneNumberUtil::getInstance();

        try {
            return $util->parse($value, PhoneNumberUtil::UNKNOWN_REGION);
        } catch (NumberParseException $e) {
            if (class_exists(ValueNotConvertible::class)) {
                throw ValueNotConvertible::new($value, self::NAME, previous: $e);
            }

            /** @phpstan-ignore-next-line */
            throw ConversionException::conversionFailed($value, self::NAME, $e);
        }
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
