<?php declare(strict_types=1);

namespace Refugis\DoctrineExtra\ODM\MongoDB\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Refugis\DoctrineExtra\TimeSpan\TimeSpanTrait;

/**
 * @ODM\EmbeddedDocument()
 */
class TimeSpan
{
    use TimeSpanTrait;

    /**
     * @ODM\Field(type="date")
     */
    private ?\DateTimeImmutable $start;

    /**
     * @ODM\Field(type="date")
     */
    private ?\DateTimeImmutable $end;
}
