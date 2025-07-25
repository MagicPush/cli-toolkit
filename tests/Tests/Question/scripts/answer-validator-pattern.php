<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Question\Question;

require_once __DIR__ . '/../../init-console.php';

$answer = Question::create('Type a natural number (integer, >= 1)')
    ->answerValidatorPattern('/^[1-9]+$/', 'Not a natural number.')
    ->ask();
echo "Your choice: {$answer}";
