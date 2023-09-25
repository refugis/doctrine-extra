<?php declare(strict_types=1);

namespace Refugis\DoctrineExtra\Tests\Fixtures\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Refugis\DoctrineExtra\ORM\Timestampable\TimestampableTrait;
use Refugis\DoctrineExtra\Timestampable\TimestampableInterface;

/**
 * @ORM\Entity()
 * @ORM\Table(name="foo_timestampable")
 */
#[ORM\Entity]
#[ORM\Table('foo_timestampable')]
class FooTimestampable implements TimestampableInterface
{
    use TimestampableTrait;

    public const ID = 42;
    public const NEW_ID = 150;

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="NONE")
     */
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue('NONE')]
    private int $id;

    public function __construct()
    {
        $this->id = self::ID;
        $this->createdAt = new \DateTimeImmutable();
        $this->updateTimestamp();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function changeId(): void
    {
        $this->id = self::NEW_ID;
    }
}
