<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\Config\Parameter;

final class Argument extends ParameterAbstract {
    public function getTitleForHelp(): string {
        return "<{$this->name}>";
    }
}
