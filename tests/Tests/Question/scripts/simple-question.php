<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Question\Question;

require_once __DIR__ . '/../../init-console.php';

echo 'Your input: ' . var_export(Question::askAway('Type something here'), true);
