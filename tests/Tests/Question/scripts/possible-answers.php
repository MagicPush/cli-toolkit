<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Question\Question;

require_once __DIR__ . '/../../init-console.php';

$possibleAnswers = unserialize($_SERVER['argv'][1]);
$isCaseSensitive = (bool) $_SERVER['argv'][2];
$errorMessage    = $_SERVER['argv'][3];

$answer = Question::create('Pick your team:')
    ->substringAfterQuestion(' > ')
    ->possibleAnswers($possibleAnswers, $isCaseSensitive, $errorMessage)
    ->ask();
echo "Chosen team: {$answer}";
