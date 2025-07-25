<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Question\Question;

require_once __DIR__ . '/../../init-console.php';

$answer = Question::create('The ultimate question of life, universe and everything else.')
    ->substringAfterQuestion($argv[1])
    ->ask();
echo "Answer: {$answer}";
