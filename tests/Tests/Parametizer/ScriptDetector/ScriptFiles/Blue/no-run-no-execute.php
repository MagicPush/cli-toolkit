<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;
use MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptLauncher\ScriptLauncher;
use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptClassDetector;

require_once __DIR__ . '/../../../../init-console.php';

// Neither of corresponding launching substrings are present.
$config   = Parametizer::newConfig();
$launcher = new ScriptLauncher(new ScriptClassDetector());
