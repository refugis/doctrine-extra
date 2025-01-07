<?php declare(strict_types=1);

namespace Refugis\DoctrineExtra\Tests\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Refugis\DoctrineExtra\ORM\Type\PhpEnumType;
use Refugis\DoctrineExtra\Tests\Fixtures\Enum\ActionEnum;
use Refugis\DoctrineExtra\Tests\Fixtures\Enum\FoobarEnum;
use Refugis\DoctrineExtra\Tests\Fixtures\Enum\NativeEnum;
use Refugis\DoctrineExtra\Tests\Fixtures\Enum\NativeIntEnum;
use Refugis\DoctrineExtra\Tests\Fixtures\Enum\NativeStringEnum;

class PhpEnumTypeTest extends TestCase
{
    use ProphecyTrait;

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        $reflection = new \ReflectionClass(Type::class);
        if ($reflection->hasProperty('typeRegistry')) {
            $property = $reflection->getProperty('typeRegistry');
            $property->setAccessible(true);
            $property->setValue(null, null);
        } else {
            $fooEnum = FoobarEnum::class;
            $multipleFooEnum = "array<$fooEnum>";
            $actionEnum = FoobarEnum::class;
            $multipleActionEnum = "array<$fooEnum>";

            foreach ([$fooEnum, $multipleFooEnum, $actionEnum, $multipleActionEnum] as $enumClass) {
                $refl = new \ReflectionClass(Type::class);
                if ($refl->hasProperty('typeRegistry')) {
                    $property = $refl->getProperty('typeRegistry');
                    $property->setAccessible(true);
                    $property->setValue(null, null);
                } else {
                    if (Type::hasType($enumClass)) {
                        Type::overrideType($enumClass, null);
                    }
                }
            }

            $reflection = new \ReflectionClass(Type::class);
            $property = $reflection->getProperty('_typesMap');
            $property->setAccessible(true);

            $value = $property->getValue(null);
            unset(
                $value[$fooEnum],
                $value[$multipleFooEnum],
                $value[$actionEnum],
                $value[$multipleActionEnum]
            );

            $property->setValue(null, $value);
        }
    }

    public function testTypesAreCorrectlyRegistered(): void
    {
        foreach ([FoobarEnum::class, ActionEnum::class] as $enumClass) {
            $multipleEnumClass = "array<$enumClass>";

            self::assertFalse(Type::hasType($enumClass));
            self::assertFalse(Type::hasType($multipleEnumClass));

            PhpEnumType::registerEnumType($enumClass);

            self::assertTrue(Type::hasType($enumClass));
            self::assertTrue(Type::hasType($multipleEnumClass));

            $type = Type::getType($enumClass);
            self::assertInstanceOf(PhpEnumType::class, $type);
            self::assertEquals($enumClass, $type->getName());

            $type = Type::getType($multipleEnumClass);
            self::assertInstanceOf(PhpEnumType::class, $type);
            self::assertEquals($multipleEnumClass, $type->getName());
        }
    }

    public function testRegisterShouldThrowIfNotAnEnumClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        PhpEnumType::registerEnumType(\stdClass::class);
    }

    public function testSQLDeclarationShouldBeCorrect(): void
    {
        $platform = $this->prophesize(AbstractPlatform::class);
        if (method_exists(AbstractPlatform::class, 'getStringTypeDeclarationSQL')) {
            $platform->getStringTypeDeclarationSQL(Argument::type('array'))->willReturn('VARCHAR(255)');
        } else {
            $platform->getVarcharTypeDeclarationSQL(Argument::type('array'))->willReturn('VARCHAR(255)');
        }

        $platform->getJsonTypeDeclarationSQL(Argument::type('array'))->willReturn('JSON');

        $enumClass = FoobarEnum::class;

        PhpEnumType::registerEnumType($enumClass);
        $type = Type::getType($enumClass);
        self::assertEquals('VARCHAR(255)', $type->getSQLDeclaration([], $platform->reveal()));

        $multipleEnumClass = "array<$enumClass>";
        $type = Type::getType($multipleEnumClass);
        self::assertEquals('JSON', $type->getSQLDeclaration([], $platform->reveal()));
    }

    public function testConvertToPHPValueShouldHandleNullValues(): void
    {
        $enumClass = FoobarEnum::class;
        $multipleEnumClass = "array<$enumClass>";

        PhpEnumType::registerEnumType($enumClass);

        $platform = $this->prophesize(AbstractPlatform::class);

        foreach ([$enumClass, $multipleEnumClass] as $target) {
            $type = Type::getType($target);

            self::assertNull($type->convertToPHPValue(null, $platform->reveal()));
            self::assertNull($type->convertToPHPValue('', $platform->reveal()));
        }
    }

    public function testConvertToPHPShouldReturnEnum(): void
    {
        $enumClass = FoobarEnum::class;
        $multipleEnumClass = "array<$enumClass>";
        PhpEnumType::registerEnumType($enumClass);

        $platform = $this->prophesize(AbstractPlatform::class);

        $type = Type::getType($enumClass);
        $value = $type->convertToPHPValue('foo', $platform->reveal());

        self::assertInstanceOf($enumClass, $value);
        self::assertEquals($enumClass::FOO(), $value);

        $type = Type::getType($multipleEnumClass);
        $value = $type->convertToPHPValue('["foo"]', $platform->reveal());

        self::assertTrue(\is_array($value));
        self::assertCount(1, $value);
        self::assertInstanceOf($enumClass, $value[0]);
        self::assertEquals($enumClass::FOO(), $value[0]);
    }

    public function testConvertToPHPShouldThrowIfNotAValidEnumValue(): void
    {
        $this->expectException(ConversionException::class);

        $platform = $this->prophesize(AbstractPlatform::class);

        $enumClass = FoobarEnum::class;
        PhpEnumType::registerEnumType($enumClass);

        $type = Type::getType($enumClass);
        $type->convertToPHPValue('boss', $platform->reveal());
    }

    public function testConvertToPHPShouldThrowIfNotAValidMultipleEnumValue(): void
    {
        $this->expectException(ConversionException::class);

        $platform = $this->prophesize(AbstractPlatform::class);

        $enumClass = FoobarEnum::class;
        $multipleEnumClass = "array<$enumClass>";
        PhpEnumType::registerEnumType($enumClass);

        $type = Type::getType($multipleEnumClass);
        $type->convertToPHPValue('["boss"]', $platform->reveal());
    }

    public function testConvertToDatabaseShouldHandleNullValues(): void
    {
        $platform = $this->prophesize(AbstractPlatform::class);

        $enumClass = FoobarEnum::class;
        $multipleEnumClass = "array<$enumClass>";
        PhpEnumType::registerEnumType($enumClass);

        $type = Type::getType($enumClass);
        self::assertNull($type->convertToDatabaseValue(null, $platform->reveal()));

        $type = Type::getType($multipleEnumClass);
        self::assertNull($type->convertToDatabaseValue(null, $platform->reveal()));
    }

    public function testConvertToDatabaseShouldReturnConstantValue(): void
    {
        $platform = $this->prophesize(AbstractPlatform::class);

        $enumClass = FoobarEnum::class;
        $multipleEnumClass = "array<$enumClass>";
        PhpEnumType::registerEnumType($enumClass);

        $type = Type::getType($enumClass);
        self::assertEquals('foo', $type->convertToDatabaseValue(FoobarEnum::FOO(), $platform->reveal()));

        $type = Type::getType($multipleEnumClass);
        self::assertEquals('["foo"]', $type->convertToDatabaseValue([FoobarEnum::FOO()], $platform->reveal()));
    }

    public function testConvertToDatabaseShouldThrowIfNotOfCorrectClass(): void
    {
        $this->expectException(ConversionException::class);

        $platform = $this->prophesize(AbstractPlatform::class);

        $enumClass = FoobarEnum::class;
        PhpEnumType::registerEnumType($enumClass);

        $type = Type::getType($enumClass);
        $type->convertToDatabaseValue(ActionEnum::GET(), $platform->reveal());
    }

    public function testConvertToDatabaseShouldThrowIfNotOfCorrectMultipleClass(): void
    {
        $this->expectException(ConversionException::class);

        $platform = $this->prophesize(AbstractPlatform::class);

        $enumClass = FoobarEnum::class;
        $multipleEnumClass = "array<$enumClass>";
        PhpEnumType::registerEnumType($enumClass);

        $type = Type::getType($multipleEnumClass);
        $type->convertToDatabaseValue([ActionEnum::GET()], $platform->reveal());
    }

    /**
     * @requires PHP >= 8.1
     */
    public function testShouldRegisterNativeEnumTypes(): void
    {
        PhpEnumType::registerEnumType(NativeEnum::class);
        PhpEnumType::registerEnumType(NativeStringEnum::class);
        PhpEnumType::registerEnumType(NativeIntEnum::class);

        self::assertInstanceOf(PhpEnumType::class, Type::getType(NativeEnum::class));
        self::assertInstanceOf(PhpEnumType::class, Type::getType(NativeStringEnum::class));
        self::assertInstanceOf(PhpEnumType::class, Type::getType(NativeIntEnum::class));
    }

    /**
     * @requires PHP >= 8.1
     */
    public function testShouldConvertNativeEnumToDatabaseAndBack(): void
    {
        PhpEnumType::registerEnumType(NativeEnum::class);
        $platform = $this->prophesize(AbstractPlatform::class);

        $value = NativeEnum::CASE_ONE;
        $type = Type::getType(NativeEnum::class);

        $dbValue = $type->convertToDatabaseValue($value, $platform->reveal());
        self::assertEquals('CASE_ONE', $dbValue);
        self::assertEquals($value, $type->convertToPHPValue($dbValue, $platform->reveal()));
    }

    /**
     * @requires PHP >= 8.1
     */
    public function testShouldThrowTryingToConvertANativeEnumForANonexistentCase(): void
    {
        PhpEnumType::registerEnumType(NativeEnum::class);
        $platform = $this->prophesize(AbstractPlatform::class);

        $type = Type::getType(NativeEnum::class);

        $this->expectException(ConversionException::class);
        $type->convertToPHPValue('CASE_NOT', $platform->reveal());
    }

    /**
     * @requires PHP >= 8.1
     */
    public function testShouldConvertNativeBackedEnumToDatabaseAndBack(): void
    {
        PhpEnumType::registerEnumType(NativeStringEnum::class);
        PhpEnumType::registerEnumType(NativeIntEnum::class);
        $platform = $this->prophesize(AbstractPlatform::class);

        $value = NativeStringEnum::CASE_ONE;
        $type = Type::getType(NativeStringEnum::class);

        $dbValue = $type->convertToDatabaseValue($value, $platform->reveal());
        self::assertEquals('one', $dbValue);
        self::assertEquals($value, $type->convertToPHPValue($dbValue, $platform->reveal()));

        $value = NativeIntEnum::CASE_TWO;
        $type = Type::getType(NativeIntEnum::class);

        $dbValue = $type->convertToDatabaseValue($value, $platform->reveal());
        self::assertEquals(2, $dbValue);
        self::assertEquals($value, $type->convertToPHPValue($dbValue, $platform->reveal()));
    }

    /**
     * @requires PHP >= 8.1
     */
    public function testShouldThrowTryingToConvertAStringBackedNativeEnumForANonexistentCase(): void
    {
        PhpEnumType::registerEnumType(NativeStringEnum::class);
        $platform = $this->prophesize(AbstractPlatform::class);

        $type = Type::getType(NativeStringEnum::class);

        $this->expectException(ConversionException::class);
        $type->convertToPHPValue('not_a_case', $platform->reveal());
    }

    /**
     * @requires PHP >= 8.1
     */
    public function testShouldThrowTryingToConvertAnIntBackedNativeEnumForANonexistentCase(): void
    {
        PhpEnumType::registerEnumType(NativeIntEnum::class);
        $platform = $this->prophesize(AbstractPlatform::class);

        $type = Type::getType(NativeIntEnum::class);

        $this->expectException(ConversionException::class);
        $type->convertToPHPValue(-1, $platform->reveal());
    }
}
