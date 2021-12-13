<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\Tests\Fixtures\Enum;

enum NativeEnum
{
    case CASE_ONE;
    case CASE_TWO;
}

enum NativeStringEnum: string
{
    case CASE_ONE = 'one';
    case CASE_TWO = 'two';
}

enum NativeIntEnum: int
{
    case CASE_ONE = 1;
    case CASE_TWO = 2;
}
