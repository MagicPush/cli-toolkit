<?php declare(strict_types=1);

use MagicPush\CliToolkit\TerminalFormatter;

require_once __DIR__ . '/../init-console.php';

echo 'some string' . PHP_EOL;
echo TerminalFormatter::createForStdOut()->apply('some stylish string', [TerminalFormatter::FONT_LIGHT_GREEN])
    . PHP_EOL;
