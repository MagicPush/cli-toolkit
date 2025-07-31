<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptLauncher\ScriptLauncher;
use MagicPush\CliToolkit\Tests\Utils\TestUtils;

require_once __DIR__ . '/../../../../init-console.php';

$config = TestUtils::newConfig(); // Not explicit config builder creation.
$config->run();

$launcherClass = ScriptLauncher::class;
$launcher      = new $launcherClass(); // Not explicit launcher creation.
$launcher->execute();
