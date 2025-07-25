<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Question\Question;

require_once __DIR__ . '/../../init-console.php';

echo 'A1: ' . Question::askAway('Q1') . PHP_EOL;
echo 'A2: ' . Question::askAway('Q2') . PHP_EOL;
echo 'A3: ' . Question::askAway('Q3') . PHP_EOL;
