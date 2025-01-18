<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\Parser;

use MagicPush\CliToolkit\Parametizer\Config\Parameter\Option;

final class Parser {
    /** @var string[] */
    private array $args;

    private bool $optionsAllowed = true;


    /**
     * @param string[] $args
     */
    public function __construct(array $args = []) {
        if (!func_num_args()) {
            $args = $_SERVER['argv'];
            array_shift($args); // Remove a filename.
        }
        $this->args = $args;
    }

    public function allowOptions(bool $state = true): void {
        $this->optionsAllowed = $state;
    }

    public function areOptionsAllowed(): bool {
        return $this->optionsAllowed;
    }

    /**
     * A plumber function needed in rare cases, when you want to add one more argument (word)
     * and then call {@see read()} again.
     */
    public function append(mixed $word) {
        $this->args[] = $word;
    }

    /**
     * Reads next argument for the script.
     *
     * @param string[] $optionsWithValuesShortNames
     */
    public function read(array $optionsWithValuesShortNames = []): ParsedOption|ParsedArgument|null {
        if (empty($this->args)) {
            return null;
        }

        $arg = array_shift($this->args);
        if ($this->areOptionsAllowed()) {
            // Only positional params are allowed after --.
            // https://unix.stackexchange.com/questions/11376/what-does-double-dash-mean
            if ('--' === $arg) {
                $this->optionsAllowed = false;

                return $this->read();
            }

            // Long name option: --name / --name= / --name=value
            if (Option::isFullNameForOption($arg)) {
                $parts = explode('=', $arg, 2);

                return new ParsedOption(
                    (isset($parts[1]) && $parts[1] !== '') ? $parts[1] : null,
                    $parts[0],
                );
            }

            // Short name option: -x / -xvalue
            if (Option::isShortNameForOption($arg)) {
                $shortName = mb_substr($arg, 1, 1);
                $value     = null;

                if (in_array($shortName, $optionsWithValuesShortNames)) {
                    // Short option with value.
                    if (mb_strlen($arg) > 2) { // -xVALUE
                        $value = mb_substr($arg, 2);
                    } elseif (count($this->args) > 0) { // -x VALUE
                        $value = array_shift($this->args);
                    }
                } elseif (mb_strlen($arg) > 2) {
                    // If we have three short options -x -y -z, they can be passed as -xyz.
                    // We got -x, put the rest back (-yz).
                    array_unshift($this->args, '-' . mb_substr($arg, 2));
                }

                return new ParsedOption($value, '-' . $shortName);
            }
        }

        // No option == positional param (argument).
        return new ParsedArgument($arg);
    }
}
