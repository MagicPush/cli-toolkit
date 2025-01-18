<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\Config\Parameter;

use MagicPush\CliToolkit\Parametizer\Exception\ConfigException;
use MagicPush\CliToolkit\Parametizer\HelpFormatter;

class Option extends ParameterAbstract {
    protected ?string $shortName = null;

    /**
     * Flag value is not the same as flag presence itself.
     *
     * While a flag's presence may be considered boolean (you see or not its presence in the request),
     * the value itself may be of any type.
     * `null` means the option is not a flag and thus requires a value.
     */
    protected mixed $flagValue = null;


    public function shortName(?string $shortName): static {
        if (null !== $shortName && !preg_match('/^[a-z]$/i', $shortName)) {
            $errorFormatter     = HelpFormatter::createForStdErr();
            $shortNameFormatted = $errorFormatter->paramTitle($shortName);
            $nameFormatted      = $errorFormatter->paramTitle($this->name);

            throw new ConfigException(
                "'{$shortNameFormatted}' ('{$nameFormatted}') >>> Config error: the short name must be a single latin character.",
            );
        }

        $this->shortName = $shortName;

        return $this;
    }

    public final function getShortName(): ?string {
        return $this->shortName;
    }

    public function flagValue(mixed $flagValue): static {
        $this->flagValue = $flagValue;

        return $this;
    }

    public function getFlagValue(): mixed {
        return $this->flagValue;
    }

    public static function isFullNameForOption(string $string): bool {
        return str_starts_with($string, '--');
    }

    public static function isShortNameForOption(string $string): bool {
        return !static::isFullNameForOption($string) && str_starts_with($string, '-');
    }

    public function isValueRequired(): bool {
        return $this->flagValue === null;
    }

    /**
     * Long name (+ optionally short name) of the option.
     */
    public function getTitleForHelp(): string {
        $helpText = "--{$this->name}";
        if (null !== $this->getShortName()) {
            $helpText .= " (-{$this->getShortName()})";
        }

        return $helpText;
    }
}
