<?php declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\CliRequest;

use Exception;
use LogicException;
use MagicPush\CliToolkit\Parametizer\Config\Config;
use MagicPush\CliToolkit\Parametizer\Config\HelpGenerator;
use MagicPush\CliToolkit\Parametizer\Config\Parameter\Argument;
use MagicPush\CliToolkit\Parametizer\Config\Parameter\Option;
use MagicPush\CliToolkit\Parametizer\Config\Parameter\ParameterAbstract;
use MagicPush\CliToolkit\Parametizer\Exception\ConfigException;
use MagicPush\CliToolkit\Parametizer\Exception\ParseErrorException;
use MagicPush\CliToolkit\Parametizer\HelpFormatter;
use MagicPush\CliToolkit\Parametizer\Parser\ParsedArgument;
use MagicPush\CliToolkit\Parametizer\Parser\ParsedOption;
use MagicPush\CliToolkit\Parametizer\Parser\Parser;

class CliRequestProcessor {
    protected Config $config;
    protected Parser $parser;

    protected ?CliRequestProcessor $detectedBranchRequest = null;
    protected ?CliRequestProcessor $parent                = null;

    protected bool $areCallbacksDisabled = false;

    /** @var Argument[] */
    protected array $argumentStack = [];

    /** @var ParameterAbstract[] */
    protected array $registeredParams = [];

    /** @var mixed[] */
    protected array $requestParams = [];

    protected array $defaultsLookup = [];


    public function disableCallbacks(bool $areCallbacksDisabled = true): static {
        $this->areCallbacksDisabled = $areCallbacksDisabled;

        return $this;
    }

    public function getParent(): ?static {
        return $this->parent;
    }

    protected function setParent(?CliRequestProcessor $requestProcessor): static {
        $this->parent = $requestProcessor;

        return $this;
    }

    public function load(Config $config, Parser $parser): CliRequest {
        $this->config        = $config;
        $this->parser        = $parser;
        $this->requestParams = [];

        $this->registeredParams = [];
        $this->defaultsLookup   = [];
        $this->argumentStack    = $this->config->getArguments();

        foreach ($this->config->getParams() as $param) {
            $this->setRequestParam($param);
        }

        $this->registerParameters();

        return new CliRequest($this->config, $this->requestParams);
    }

    /**
     * A plumber function needed in rare cases, when you want to process (pass) an additional argument (word) after you
     * call {@see load()}.
     */
    public function append(mixed $word) {
        if ($this->detectedBranchRequest) {
            $this->detectedBranchRequest->append($word);

            return;
        }

        if (!isset($this->parser)) {
            throw new LogicException('Please call "load()" method first.');
        }

        $this->parser->append($word);

        $this->registerParameters();
    }

    protected function setRequestParam(ParameterAbstract $param, mixed $value = null): void {
        if (!$param->isVisibleIn(Config::VISIBLE_REQUEST)) {
            return;
        }

        $name    = $param->getName();
        $isArray = $param->isArray();

        if (1 === func_num_args()) {
            // Initialization mode.
            $value = $param->getDefault();
            // Absent value should not be wrapped in array.
            $isArray = false;

            $this->defaultsLookup[$name] = true;
        } else {
            $errorFormatter      = HelpFormatter::createForStdErr();
            $paramTitleFormatted = $errorFormatter->paramTitle($param->getTitleForHelp());

            // Forbid specifying parameters more than once if those parameters do not allow multiple values:
            if (!$isArray && $this->isRegistered($param)) {
                $errorMessage = 'Duplicate';

                $errorMessage .= $param instanceof Option
                    ? " option {$paramTitleFormatted}"
                    : " argument {$paramTitleFormatted}";
                $errorMessage .= (true !== $value)
                    ? " (with value '" . $errorFormatter->paramValue(HelpGenerator::convertValueToString($value)) . "')"
                    : ' (as a flag)';

                $errorMessage .= "; already registered ";
                $errorMessage .= (true !== $this->requestParams[$name])
                    ? "value: '" . $errorFormatter->paramValue(HelpGenerator::convertValueToString($this->requestParams[$name])) . "'"
                    : 'as a flag';

                throw new ParseErrorException(
                    $errorMessage,
                    ParseErrorException::E_NO_OPTION_VALUE,
                    [$param],
                );
            }

            // Forbid duplicate values for a parameter:
            if ($isArray && $this->isRegistered($param) && in_array($value, $this->requestParams[$name])) {
                $paramErrorHelp = $param instanceof Option
                    ? "option {$paramTitleFormatted}"
                    : "argument {$paramTitleFormatted}";

                $registeredValuesFormatted = [];
                foreach ($this->requestParams[$name] as $registeredValue) {
                    $registeredValuesFormatted[] = $errorFormatter->paramValue(
                        HelpGenerator::convertValueToString($registeredValue),
                    );
                }
                $registeredValuesHelp = implode("', '", $registeredValuesFormatted);

                $valueHelp = $errorFormatter->paramValue(HelpGenerator::convertValueToString($value));

                throw new ParseErrorException(
                    "Duplicate value '{$valueHelp}' for {$paramErrorHelp}; already registered values: '{$registeredValuesHelp}'",
                    ParseErrorException::E_NO_OPTION_VALUE,
                    [$param],
                );
            }

            // An actual value was registered.
            $this->registeredParams[] = $param;
        }

        if ($isArray) {
            if (!isset($this->requestParams[$name]) || !is_array($this->requestParams[$name])) {
                $this->requestParams[$name] = [];
            }

            // If the default value is an array, we need to clean it up before inserting actual first value.
            if (!empty($this->defaultsLookup[$name])) {
                $this->requestParams[$name] = [];
                unset($this->defaultsLookup[$name]);
            }

            $this->requestParams[$name][] = $value;
        } else {
            $this->requestParams[$name] = $value;
        }
    }

    protected function isRegistered(ParameterAbstract $param): bool {
        return in_array($param, $this->registeredParams);
    }

    /**
     * @return Option[]
     */
    protected static function getMissingRequiredOptions(CliRequestProcessor $requestProcessor): array {
        $missingOptions = [];

        $parentRequestProcessor = $requestProcessor->getParent();
        if ($parentRequestProcessor) {
            $missingOptions = static::getMissingRequiredOptions($parentRequestProcessor);
        }

        foreach ($requestProcessor->getConfig()->getOptions() as $option) {
            if ($option->isRequired() && !$requestProcessor->isRegistered($option)) {
                $missingOptions[] = $option;
            }
        }

        return $missingOptions;
    }

    protected function registerParameters(): void {
        $options = $this->config->getOptionsByFormattedNamesAndShortNames();
        while ($parsedParam = $this->parser->read($this->config->getOptionsWithValuesShortNames())) {
            if ($parsedParam instanceof ParsedOption) {
                $this->registerOption($options, $parsedParam);
            } else { /** @var ParsedArgument $parsedParam */
                $this->registerArgument($this->argumentStack, $parsedParam);
            }
        }
    }

    /**
     * @param Option[] $options
     */
    protected function registerOption(array $options, ParsedOption $parsedOption): void {
        $errorFormatter = HelpFormatter::createForStdErr();

        if (!isset($options[$parsedOption->alias])) {
            throw new ParseErrorException(
                "Unknown option '" . $errorFormatter->paramTitle($parsedOption->alias) . "'",
                ParseErrorException::E_WRONG_OPTION,
            );
        }

        // param.name is actually an alias (--smth)
        $option = $options[$parsedOption->alias];

        // There is no value specified for option and that option requires a value:
        if (null === $parsedOption->value && $option->isValueRequired()) {
            throw new ParseErrorException(
                'No value for option ' . $errorFormatter->paramTitle($option->getTitleForHelp()),
                ParseErrorException::E_NO_OPTION_VALUE,
                [$option],
            );
        }

        // A value is specified but that option is a flag and cannot have a specific value:
        if (null !== $parsedOption->value && !$option->isValueRequired()) {
            throw new ParseErrorException(
                'The flag ' . $errorFormatter->paramTitle($option->getTitleForHelp()) . ' can not have a value',
                ParseErrorException::E_NO_OPTION_VALUE,
                [$option],
            );
        }

        $value = $parsedOption->value ?? $option->getFlagValue();

        $this->validateParam($option, $value, ParseErrorException::E_NO_OPTION_VALUE);
        $this->setRequestParam($option, $value);
        if (!$this->areCallbacksDisabled) {
            $option->runCallback($value);
        }
    }

    /**
     * @param Argument[] $arguments
     */
    protected function registerArgument(array &$arguments, ParsedArgument $parsedArgument): void {
        $errorFormatter = HelpFormatter::createForStdErr();
        $value          = $parsedArgument->value;

        if (empty($arguments)) {
            throw new ParseErrorException(
                "Too many arguments, starting with '" . $errorFormatter->paramValue((string) $value) . "'",
                ParseErrorException::E_TOO_MANY_ARGUMENTS,
                $this->getInnermostBranchConfig()->getArguments(),
            );
        }

        $argument = reset($arguments);
        if (!$argument->isArray()) {
            array_shift($arguments);
        }

        $this->validateParam($argument, $value, ParseErrorException::E_WRONG_ARGUMENT);
        $this->setRequestParam($argument, $value);
        if (!$this->areCallbacksDisabled) {
            $argument->runCallback($value);
        }

        if ($argument->isSubcommandSwitch()) {
            $branch = $this->getConfig()->getBranch($value);

            // This shouldn't happen because of guaranteed validator for such a parameter. But if it still somehow happens...
            if (!$branch) {
                throw new ParseErrorException(
                    "Unknown command '" . $errorFormatter->paramValue((string) $value) . "'",
                    ParseErrorException::E_WRONG_ARGUMENT,
                    [$argument],
                );
            }

            // The rest of arguments are treated as a separate request to the subcommand.
            $requestProcessor            = (new static())->setParent($this);
            $this->detectedBranchRequest = $requestProcessor;

            // Options are allowed again.
            $this->parser->allowOptions();

            $consoleRequest = $requestProcessor->load($branch, $this->parser);

            $this->requestParams[$value] = $consoleRequest->getParams();
        }
    }

    /**
     * Generate error with custom validation messages when validation fails.
     */
    protected function validateParam(ParameterAbstract $param, mixed &$value, int $errorCode): void {
        $originalValue  = $value;
        $errorFormatter = HelpFormatter::createForStdErr();

        $customExceptionMessage = null;
        $isValid                = false;
        try {
            $isValid = $param->validate($value);
        } catch (ConfigException $e) {
            // Save previous behavior for ConfigException.
            throw $e;
        } catch (Exception $e) {
            $customExceptionMessage = $e->getMessage();
        }

        if (!$isValid) {
            $additionalMessage = $customExceptionMessage ?? $param->getValidatorCustomMessage() ?? null;
            throw new ParseErrorException(
                "Incorrect value '" . $errorFormatter->paramValue(HelpGenerator::convertValueToString($originalValue))
                . "' for " . (($param instanceof Option) ? 'option' : 'argument') . " "
                . $errorFormatter->paramTitle($param->getTitleForHelp())
                . ($additionalMessage ? '. ' . $additionalMessage : null),
                $errorCode,
                [$param],
            );
        }
    }

    public function getConfig(): Config {
        return $this->config;
    }

    public function getInnermostBranchConfig(): Config {
        if ($this->detectedBranchRequest) {
            return $this->detectedBranchRequest->getInnermostBranchConfig();
        }

        return $this->getConfig();
    }

    /**
     * Validates the request state after it has been loaded.
     */
    public function validate(): void {
        $this->detectedBranchRequest?->validate();

        $missingOptions   = static::getMissingRequiredOptions($this);
        $missingArguments = [];

        // Allowed leftover arguments:
        // * non-required arguments
        // * required array arguments with non-empty arrays
        foreach ($this->argumentStack as $argument) {
            if (!$argument->isRequired()) {
                continue;
            }

            if ($argument->isArray() && $this->isRegistered($argument)) {
                continue;
            }

            $missingArguments[] = $argument;
        }
        if ($missingArguments) {
            throw new ParseErrorException(
                'Need more parameters',
                ParseErrorException::E_NO_PARAM,
                array_merge($missingOptions, $missingArguments), // Show options at first and only then - arguments.
            );
        }

        $errorFormatter        = HelpFormatter::createForStdErr();
        $optionTitlesFormatted = [];
        foreach ($missingOptions as $option) {
            $optionTitlesFormatted[$option->getName()] = $errorFormatter->paramTitle($option->getTitleForHelp());
        }

        if (!empty($missingOptions)) {
            $errorMessageList   = join(', ', $optionTitlesFormatted);
            $errorMessagePrefix = count($optionTitlesFormatted) > 1 ? 'Need values' : 'Need a value';

            throw new ParseErrorException(
                "{$errorMessagePrefix} for {$errorMessageList}",
                ParseErrorException::E_NO_OPTION,
                $missingOptions,
            );
        }
    }

    /**
     * @return Argument[]
     */
    public function getAllowedArguments(): array {
        if ($this->detectedBranchRequest) {
            return $this->detectedBranchRequest->getAllowedArguments();
        }

        if (empty($this->argumentStack)) {
            return [];
        }

        $arguments = [];
        reset($this->argumentStack);
        do {
            // We need all non-required arguments and the first required.
            $argument    = current($this->argumentStack);
            $arguments[] = $argument;
        } while (next($this->argumentStack) && !$argument->isRequired());

        return $arguments;
    }

    /**
     * Returns values (by names) for all actually registered parameters: (string) name without hyphens => (mixed) value
     *
     * @return mixed[]
     */
    public function getInnermostBranchRegisteredValues(): array {
        if ($this->detectedBranchRequest) {
            return $this->detectedBranchRequest->getInnermostBranchRegisteredValues();
        }

        $registeredValues = [];

        foreach ($this->registeredParams as $registeredParameter) {
            $registeredParameterName                    = $registeredParameter->getName();
            $registeredValues[$registeredParameterName] = $this->requestParams[$registeredParameterName];
        }

        return $registeredValues;
    }
}
