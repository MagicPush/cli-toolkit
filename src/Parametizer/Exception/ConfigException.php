<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\Exception;

use LogicException;
use MagicPush\CliToolkit\Parametizer\HelpFormatter;
use Throwable;

final class ConfigException extends LogicException {
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null) {
        parent::__construct(HelpFormatter::createForStdErr()->error($message), $code, $previous);
    }
}
