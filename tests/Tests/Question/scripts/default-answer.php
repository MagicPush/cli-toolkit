<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Question\Question;

require_once __DIR__ . '/../../init-console.php';

$answer = Question::create('What would you like to drink?')
    ->defaultAnswer('tea')
    ->ask();
echo "Answer: {$answer}";
