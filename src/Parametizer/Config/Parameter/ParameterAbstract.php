<?php declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\Config\Parameter;

use MagicPush\CliToolkit\Parametizer\Config\Config;
use MagicPush\CliToolkit\Parametizer\Exception\ConfigException;
use MagicPush\CliToolkit\Parametizer\HelpFormatter;
use Traversable;

abstract class ParameterAbstract {
    protected readonly string $name;

    protected string $description        = '';
    protected bool   $isRequired         = false;
    protected bool   $isArray            = false;
    protected bool   $isSubcommandSwitch = false;
    protected int    $visibilityBitmask  = Config::VISIBILITY_BITMASK_ALL;
    protected mixed  $default            = null;

    /** @var callable|null */
    protected $callback;

    /** @var callable|string|null callback / regexp */
    protected         $validator;
    protected ?string $validatorCustomMessage;

    /** @var callable|string[]|null */
    protected $completion;

    /** @var string[]|null[] */
    protected array $allowedValues = [];

    public function __construct(string $name) {
        $errorMessagePrefix = "'" . HelpFormatter::createForStdErr()->paramTitle($name) . "' >>> Config error:";
        if (mb_strlen($name) < 2) {
            throw new ConfigException("{$errorMessagePrefix} too short param name; must contain at least 2 symbols.");
        }

        if (!preg_match('/^[a-z][a-z\d_\-]+$/u', $name)) {
            throw new ConfigException(
                "{$errorMessagePrefix} invalid characters. Must start with latin (lower);"
                . ' the rest symbols may be of latin (lower), digit, underscore or hyphen.',
            );
        }
        $this->name = $name;
    }

    public final function getName(): string {
        return $this->name;
    }

    public function description(string $description): static {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): string {
        return $this->description;
    }

    public function require(bool $isRequired = true): static {
        $this->isRequired = $isRequired;

        return $this;
    }

    public function isRequired(): bool {
        return $this->isRequired;
    }

    public function setIsArray(bool $isArray = true): static {
        $this->isArray = $isArray;

        return $this;
    }

    public function isArray(): bool {
        return $this->isArray;
    }

    public function setIsSubcommandSwitch(bool $isSubcommandsName = true): static {
        $this->isSubcommandSwitch = $isSubcommandsName;

        return $this;
    }

    public function isSubcommandSwitch(): bool {
        return $this->isSubcommandSwitch;
    }

    public function isVisibleIn(int $visibilityBitValue): bool {
        return (bool) ($this->getVisibilityBitmask() & $visibilityBitValue);
    }

    public function visibilityBitmask(int $visibilityBitmask): static {
        $this->visibilityBitmask = $visibilityBitmask;

        return $this;
    }

    public function getVisibilityBitmask(): int {
        return $this->visibilityBitmask;
    }

    public function callback(?callable $callback): static {
        $this->callback = $callback;

        return $this;
    }

    public function getCallback(): ?callable {
        return $this->callback;
    }

    public function validator(callable|string|null $validator, ?string $validatorCustomMessage = null): static {
        $this->validator              = $validator;
        $this->validatorCustomMessage = $validatorCustomMessage;

        return $this;
    }

    public function getValidator(): callable|string|null {
        return $this->validator;
    }

    public function getValidatorCustomMessage(): ?string {
        return $this->validatorCustomMessage;
    }

    /**
     * @param callable|string[]|null $completion
     */
    public function completion(callable|array|null $completion): static {
        $this->completion = $completion;

        return $this;
    }

    /**
     * @return callable|string[]|null
     */
    public function getCompletion(): callable|array|null {
        return $this->completion;
    }

    /**
     * @param string[]|null[] $allowedValues
     */
    public function allowedValues(array $allowedValues): static {
        if (!empty($allowedValues)) {
            $this->validator(fn($value) => array_key_exists($value, $allowedValues));
            $this->completion(array_keys($allowedValues));
        } elseif (!empty($this->allowedValues)) {
            $this->validator(null);
            $this->completion(null);
        }

        $this->allowedValues = $allowedValues;

        return $this;
    }

    /**
     * @return string[]|null[] value => null or (string) description
     */
    public function getAllowedValues(): array {
        return $this->allowedValues;
    }

    public function default(mixed $default): static {
        $this->default = $default;

        return $this;
    }

    public function getDefault(): mixed {
        return $this->default;
    }

    public function validate(mixed &$value): mixed {
        $validator = $this->getValidator();

        if (null !== $validator) {
            if (is_callable($validator)) {
                return call_user_func_array($validator, [&$value]);
            }

            if (is_string($validator)) {
                $matchResult = preg_match($validator, $value);
                if (false !== $matchResult) {
                    return $matchResult;
                }
            }

            throw new ConfigException(
                "'" . HelpFormatter::createForStdErr()->paramTitle($this->getName())
                    . "' >>> Config error: invalid validator"
            );
        }

        return true;
    }

    public function runCallback(): void {
        if (null !== $this->getCallback()) {
            if (!is_callable($this->getCallback())) {
                throw new ConfigException(
                    "'" . HelpFormatter::createForStdErr()->paramTitle($this->getName())
                    . "' >>> Config error: non-callable callback",
                );
            }

            $args = func_get_args();
            call_user_func_array($this->getCallback(), $args);
        }
    }

    /**
     * @return mixed[]|Traversable Any type here.
     */
    public function complete(string $enteredValue): iterable {
        $completion = $this->getCompletion();

        if (!is_array($completion) && is_callable($completion)) {
            $completion = call_user_func($completion, $enteredValue);
        }

        if (is_array($completion) || ($completion instanceof Traversable)) {
            return $completion;
        }

        return [];
    }

    abstract public function getTitleForHelp(): string;
}
