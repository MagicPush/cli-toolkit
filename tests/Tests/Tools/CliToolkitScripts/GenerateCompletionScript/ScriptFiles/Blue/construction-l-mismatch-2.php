<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptClassLauncher\ScriptClassLauncher;
use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptClassDetector;
use MagicPush\CliToolkit\Tests\Utils\TestUtils;

require_once __DIR__ . '/../../../../../init-console.php';

$launcher = new ScriptClassLauncher(new ScriptClassDetector());
TestUtils::newConfig()
    ->run();
