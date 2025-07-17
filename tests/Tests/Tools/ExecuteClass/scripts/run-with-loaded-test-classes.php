<?php

declare(strict_types=1);

// The source and test classes autoloader:
require_once __DIR__ . '/' . '../../../init-console.php';
// Manual loading for the class without a namespace:
require_once __DIR__ . '/../Classes/SomeClassNoNamespace.php';

// The class executor itself:
require_once __DIR__ . '/' . '../../../../../tools/cli-toolkit/execute-class.php';
