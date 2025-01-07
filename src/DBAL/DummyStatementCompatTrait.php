<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\DBAL;

use Composer\InstalledVersions;

use function version_compare;

if (version_compare((string) InstalledVersions::getVersion('doctrine/dbal'), '4.0.0', '>=')) {
    trait DummyStatementCompatTrait
    {
        use DummyResultCompatTraitV4;
        use DummyStatementCompatTraitV4;
    }
} else {
    trait DummyStatementCompatTrait // phpcs:ignore PSR1.Classes.ClassDeclaration.MultipleClasses
    {
        use DummyResultCompatTraitV2;
        use DummyStatementCompatTraitV2;
    }
}
