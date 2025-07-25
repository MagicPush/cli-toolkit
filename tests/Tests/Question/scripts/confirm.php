<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Question\Question;

require_once __DIR__ . '/../../init-console.php';

echo 'Confirmed: ' . (Question::confirm("Do you confirm it?") ? 'yes' : 'no');
