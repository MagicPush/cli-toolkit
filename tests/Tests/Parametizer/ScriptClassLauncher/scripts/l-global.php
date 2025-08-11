<?php

declare(strict_types=1);

// Explicitly use main autoloader here (like it would be done in some project that utilizes the library).
// That will "naturally" prevent tests and tools classes autoloading.
require_once __DIR__ . '/../../../../../vendor/autoload.php';

use MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptClassLauncher\ScriptClassLauncher;
use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptClassDetector;

$scriptClassDetector = ScriptClassDetector::create(throwOnException: true)
    ->searchDirectory(__DIR__ . '/../../../../../../cli-toolkit', isRecursive: true);
ScriptClassLauncher::create($scriptClassDetector)
    ->throwOnException(isEnabled: true)
    ->execute();
