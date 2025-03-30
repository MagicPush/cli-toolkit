<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\Config\Builder;

use MagicPush\CliToolkit\Parametizer\Exception\ConfigException;
use MagicPush\CliToolkit\Parametizer\HelpFormatter;

abstract class VariableBuilderAbstract extends BuilderAbstract {
    protected ?bool $manualIsRequired = null;
    protected mixed $manualDefault    = null;

    /** @var callable|string|null */
    protected $manualValidator = null;

    /** @var callable|string[]|null */
    protected $manualCompletion = null;

    /** @var string[]|null[] */
    protected array $manualAllowedValues = [];


    // === Parameter settings ===

    public function required(bool $isRequired = true): static {
        $this->manualIsRequired = $isRequired;
        $this->ensureNotRequiredAndHasDefaultSimultaneously();

        $this->param->require($isRequired);

        return $this;
    }

    /**
     * PCRE regexp as a parameter value validator (null = no validation).
     *
     * If a parameter can have multiple values, the validator runs on each value separately.
     */
    public function validatorPattern(?string $pattern, ?string $validatorCustomMessage = null): static {
        return $this->validator($pattern, $validatorCustomMessage);
    }

    /**
     * Validator callback (null = no validation).
     *
     * Validator will be executed immediately when a value is scanned from input parameters.
     * If a parameter can have multiple values, the validator runs on each value separately.
     *
     * Validator callback has such a signature: `(&$value): bool`.
     * The callback may modify the value (e.g. trim, canonicalize, etc).
     *  * If the value is modified in validator, but then an exception occurs,
     *      the error message will contain the original value before its modification.
     *  * If your callback declares `$value` argument without reference, `$value` will be passed by a reference anyway!
     *
     * Validator also runs during autocomplete. Make sure the callback is not throwing errors or exits the process,
     * otherwise use {@see BuilderAbstract::callback()} instead.
     */
    public function validatorCallback(?callable $callback, ?string $validatorCustomMessage = null): static {
        return $this->validator($callback, $validatorCustomMessage);
    }

    protected function validator(callable|string|null $validator, ?string $validatorCustomMessage = null): static {
        $this->manualValidator = $validator;
        $this->ensureNotAllowedValuesSetWithValidatorOrCompletionSimultaneously();

        $this->param->validator($validator, $validatorCustomMessage);

        return $this;
    }

    /**
     * Autocomplete values list
     *
     * @param string[] $values
     */
    public function completionList(array $values): static {
        return $this->completion($values);
    }

    /**
     * Callback to provide values for autocomplete (null = no values to complete).
     *
     * Callback should have this signature: `($enteredValue): string[]`.
     */
    public function completionCallback(?callable $callback): static {
        return $this->completion($callback);
    }

    /**
     * @param callable|string[]|null $completion
     */
    protected function completion(callable|array|null $completion): static {
        $this->manualCompletion = $completion;
        $this->ensureNotAllowedValuesSetWithValidatorOrCompletionSimultaneously();

        $this->param->completion($completion);

        return $this;
    }

    /**
     * Allowed values for a parameter.
     *
     * This will automatically set {@see validatorCallback()} and {@see completionList()}.
     *
     * @param mixed[] $allowedValues
     * @param bool    $areHiddenForHelp If the list should not be shown on a generated help page.
     *                                  Useful for really long lists.
     */
    public function allowedValues(array $allowedValues, bool $areHiddenForHelp = false): static {
        $allowedValues = array_fill_keys(array_values($allowedValues), null);

        return $this->setAllowedValues($allowedValues, $areHiddenForHelp);
    }

    /**
     * Allowed values for a parameter with descriptions: value => 'description for the help'|null
     * (`null` means no description).
     *
     * This will automatically set {@see validatorCallback()} and {@see completionList()}.
     *
     * Examples:
     * 1. Descriptions for all values: ['up' => 'moves token up', 'down' => 'moves token down']
     * 2. Mixed list: ['brother' => null, 'sister' => null, 'cousin' => 'anything other than brother or sister']
     *
     * @param string[]|null[] $allowedValues
     */
    public function allowedValuesDescribed(array $allowedValues): static {
        return $this->setAllowedValues($allowedValues, false);
    }

    /**
     * Default value for a parameter.
     * If the default is provided, parameter is no longer required (same as calling {@see required()} with `false`).
     */
    public function default(mixed $default): static {
        $this->manualDefault = $default;
        $this->ensureNotRequiredAndHasDefaultSimultaneously();

        $this->param->default($default);

        // Default is set == param is not required
        if (null !== $this->param->getDefault() && $this->param->isRequired()) {
            $this->param->require(false);
        }

        return $this;
    }


    // === Misc ===

    protected function setAllowedValues(array $allowedValues, bool $areHiddenForHelp): static {
        $this->manualAllowedValues = $allowedValues;
        $this->ensureNotAllowedValuesSetWithValidatorOrCompletionSimultaneously();

        $this->param->allowedValues($allowedValues, $areHiddenForHelp);

        return $this;
    }

    protected function ensureNotRequiredAndHasDefaultSimultaneously(): void {
        // We do not allow to require a param and simultaneously have a default value for it.
        if (
            !empty($this->manualIsRequired)
            && isset($this->manualDefault)
        ) {
            throw new ConfigException(
                "'" . HelpFormatter::createForStdErr()->paramTitle($this->param->getName())
                . "' >>> Config error: a parameter can't be required and have a default simultaneously.",
            );
        }
    }

    protected function ensureNotAllowedValuesSetWithValidatorOrCompletionSimultaneously(): void {
        if (empty($this->manualAllowedValues)) {
            return;
        }

        $errorMessagePrefix = "'" . HelpFormatter::createForStdErr()->paramTitle($this->param->getName())
            . "' >>> Config error:";
        if (isset($this->manualValidator)) {
            throw new ConfigException("{$errorMessagePrefix} do not set allowed values and validation simultaneously.");
        }
        if (isset($this->manualCompletion)) {
            throw new ConfigException("{$errorMessagePrefix} do not set allowed values and completion simultaneously.");
        }
    }
}
