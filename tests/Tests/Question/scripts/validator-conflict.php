<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Question\Question;

require_once __DIR__ . '/../../init-console.php';

$isListBeforePattern = (bool) $_SERVER['argv'][1];

$answer = Question::create('Does not matter');
if ($isListBeforePattern) {
    $answer
        ->possibleAnswers(['a', 'b'])
        ->answerValidatorPattern('/^[0-9]+$/');
} else {
    $answer
        ->answerValidatorPattern('/^[0-9]+$/')
        ->possibleAnswers(['a', 'b']);
}
