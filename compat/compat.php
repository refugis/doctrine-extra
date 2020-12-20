<?php declare(strict_types=1);

require_once __DIR__.'/Common/Persistence/Event/LifecycleEventArgs.php';
require_once __DIR__.'/Common/Persistence/Mapping/Driver/MappingDriver.php';
require_once __DIR__.'/Common/Persistence/Mapping/ClassMetadata.php';
require_once __DIR__.'/Common/Persistence/Mapping/ClassMetadataFactory.php';
require_once __DIR__.'/Common/Persistence/Mapping/MappingException.php';
require_once __DIR__.'/Common/Persistence/Mapping/RuntimeReflectionService.php';
require_once __DIR__.'/Common/Persistence/ObjectRepository.php';
require_once __DIR__.'/DBAL/Exception.php';
require_once __DIR__.'/DBAL/Result.php';
