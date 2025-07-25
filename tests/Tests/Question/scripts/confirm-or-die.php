<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Question\Question;

require_once __DIR__ . '/../../init-console.php';

Question::confirmOrDie('Confirm to proceed', $_SERVER['argv'][1] ?? '');
echo 'Execution commencing...';
