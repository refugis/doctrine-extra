<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\ODM\MongoDB\Document;

use DateTimeImmutable;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Refugis\DoctrineExtra\TimeSpan\TimeSpanTrait;

/** @ODM\EmbeddedDocument() */
class TimeSpan
{
    use TimeSpanTrait;

    /** @ODM\Field(type="date") */
    private DateTimeImmutable|null $start; // phpcs:ignore SlevomatCodingStandard.Classes.UnusedPrivateElements.UnusedProperty

    /** @ODM\Field(type="date") */
    private DateTimeImmutable|null $end; // phpcs:ignore SlevomatCodingStandard.Classes.UnusedPrivateElements.UnusedProperty
}
