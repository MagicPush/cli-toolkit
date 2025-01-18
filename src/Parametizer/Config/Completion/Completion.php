<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\Config\Completion;

use Exception;
use MagicPush\CliToolkit\Parametizer\CliRequest\CliRequestProcessor;
use MagicPush\CliToolkit\Parametizer\Config\Config;
use MagicPush\CliToolkit\Parametizer\Config\Parameter\Option;
use MagicPush\CliToolkit\Parametizer\Config\Parameter\ParameterAbstract;
use MagicPush\CliToolkit\Parametizer\Parser\Parser;
use RuntimeException;

final class Completion {
    public const string COMP_WORDBREAKS = PHP_EOL . " \t\"'><=;|&(:";

    private readonly Parser $parser;
    private readonly CliRequestProcessor $cliRequestProcessor;

    /**
     * @var Option[]
     * @see Config::getOptionsByFormattedNamesAndShortNames()
     */
    private readonly array $innermostOptionsByAllNames;


    private function __construct(private readonly Config $config) { }

    /**
     * Calculates the completion options.
     *
     * @param string $command        Full command to be completed.
     * @param int    $cursorOffset   Position of the cursor.
     * @param string $compWordBreaks For meaning of COMP_WORDBREAKS refer to bash manual.
     * @return string[]
     */
    private function complete(
        string $command,
        int $cursorOffset,
        string $compWordBreaks = self::COMP_WORDBREAKS,
    ): array {
        $tokenizer = new Tokenizer($command);

        $prevWord  = null;
        $lastToken = null;
        $words     = [];
        while ($nextToken = $tokenizer->read($cursorOffset)) {
            $lastToken = $nextToken;
            $words[]   = $nextToken->word;
        }

        array_shift($words); // The first one is the executable name.
        if (empty($words)) {
            $lastToken = null;
        } else {
            array_pop($words); // The last one is being completed.

            /*
             * In case '-o val' we have 2 tokens: the last one contains a value (or a part of it) - 'val',
             * the previous one contains a short option name - '-o'.
             * In other cases the previous value is absent (there is just one token in total)
             * or just irrelevant for the autocompletion context (only the last token is being completed).
             */
            $prevWord = array_pop($words);
            /*
             * However if we have a case like '-o val something'
             * (when an option short name and it's value are specified, but the next token should be completed),
             * we should push the popped token back, so '-o val' is not separated into '-o' and 'val`,
             * thus processed successfully.
             */
            if (
                null !== $prevWord
                && (!str_starts_with($prevWord, '-') || '--' === $prevWord)
            ) {
                $words[]  = $prevWord;
                $prevWord = null;
            }
        }

        // Let's fire up CliRequestProcessor with the rest of passed data.
        $this->parser              = new Parser($words);
        $this->cliRequestProcessor = (new CliRequestProcessor($this->config))
            ->disableCallbacks();

        $this->cliRequestProcessor->load($this->parser);

        // If the last two tokens may be treated as options...
        if ($this->parser->areOptionsAllowed()) { // Same as ensuring no '--' is found in $words.
            // At the moment we can not treat short option names and thus autocomplete values properly for such cases.
            // So let's try detecting a short option name and, if successful, convert it into a full name.

            $this->innermostOptionsByAllNames = $this->cliRequestProcessor
                ->getInnermostBranchConfig()
                ->getOptionsByFormattedNamesAndShortNames();

            if (null !== $prevWord && array_key_exists($prevWord, $this->innermostOptionsByAllNames)) {
                // Case '-o val', where '-o' is found as one of options by a short name.

                $option = $this->innermostOptionsByAllNames[$prevWord];
                if ($option->isValueRequired()) {
                    $lastToken = new Token(
                        "--{$option->getName()}={$lastToken->arg}",
                        "--{$option->getName()}={$lastToken->word}",
                    );

                    // Here is the only case when a previous token is truly a part of an actual token - an option name.
                    // It just has been used to compile a full token, so let's nullify it here.
                    // Otherwise later this word will be added to a pre-parsed list of tokens,
                    // which is critical for argument values completion.
                    $prevWord = null;
                }
            } elseif (null !== $lastToken && '--' !== $prevWord && str_starts_with($lastToken->word, '-')) {
                /*
                 * And here we have one of the following:
                 * a) '-o' (an option's short name only);
                 * b) '-oval' (an option's short name + a value or a part of it)
                 * c) '--' that may count as a full option name (`--option`)
                 */

                $lastWordShortName = mb_substr($lastToken->word, 0, 2);
                if (array_key_exists($lastWordShortName, $this->innermostOptionsByAllNames)) {
                    $option = $this->innermostOptionsByAllNames[$lastWordShortName];
                    if ($option->isValueRequired()) {
                        $lastToken = new Token(
                            "--{$option->getName()}=" . mb_substr($lastToken->arg, 2),
                            "--{$option->getName()}=" . mb_substr($lastToken->word, 2),
                        );
                    }
                }
            }
        }

        // Let's add $prevWord to a pre-parsed list of tokens now,
        // unless it has been nullified when used as an option name.
        if ($prevWord) {
            $this->cliRequestProcessor->appendToInnermostBranch($prevWord);
        }

        try {
            $allPossibleCompletions = $this->completeConfig($lastToken ? $lastToken->word : '');

            return $this->getCompletionsLimitedByToken($allPossibleCompletions, $lastToken, $compWordBreaks);
        } catch (Exception $e) {
            /*
             * Printing an error explaining why autocomplete does not work.
             * Printing to STDERR. If you print to STDOUT here, output will be broken:
             * - if there is a newline in the message, bash prints second line first;
             * - Tab symbol is printed as "^I";
             * - Special symbols (e.g. color sequences) are escaped before printing;
             * - Leading newlines are stripped.
             *
             * Newline before error text is necessary, otherwise error text will mix with the entered command.
             */
            fwrite(STDERR, PHP_EOL . $e->getMessage() . PHP_EOL);

            // Adding extra lines to STDERR (like a help page) is pointless here,
            // because in this case only a single line is shown in STDERR.

            return [];
        }
    }

    /**
     * @return string[]
     */
    private function completeConfig(string $currentArg): array {
        $completions      = [];
        $registeredValues = $this->cliRequestProcessor->getInnermostBranchRegisteredValues();

        if ($this->parser->areOptionsAllowed()) {
            if (str_starts_with($currentArg, '-')) {
                $this->completeOptions($this->innermostOptionsByAllNames, $completions);
            }

            if (preg_match('/^((--[^\s=]+)=)(.*)$/', $currentArg, $matches)) {
                // $matches[2]: option name with dashes but without "=".
                $option = $this->innermostOptionsByAllNames[$matches[2]] ?? null;

                if ($option) {
                    // $matches[3]: part of parameter value entered by the user.
                    $this->completeParamValue($completions, $registeredValues, $matches[3], $option, $matches[1]);
                }
            }
        }

        foreach ($this->cliRequestProcessor->getInnermostBranchAllowedArguments() as $argument) {
            if (!$argument->isVisibleIn(Config::VISIBLE_COMPLETION)) {
                continue;
            }

            $this->completeParamValue($completions, $registeredValues, $currentArg, $argument);
        }

        return $completions;
    }

    /**
     * @param Option[] $options
     * @param string[] $completions
     */
    private function completeOptions(array $options, array &$completions): void {
        $registeredValues = $this->cliRequestProcessor->getInnermostBranchRegisteredValues();

        foreach ($options as $alias => $option) {
            // Ignore short names.
            if (mb_strlen($alias) === 2) {
                continue;
            }

            if (!$option->isVisibleIn(Config::VISIBLE_COMPLETION)) {
                continue;
            }

            $optionRegisteredValues = $registeredValues[$option->getName()] ?? [];

            // Do not complete an already registered option name, unless it supports multiple values.
            if (!$option->isArray() && $optionRegisteredValues) {
                continue;
            }

            // Do not complete an array option name, if all allowed values have been already registered.
            if (
                $option->isArray() && $optionRegisteredValues
                && count($optionRegisteredValues) == count($option->complete(''))
            ) {
                continue;
            }

            // If there is a value, we shouldn't add a space, so the value can be completed right away.
            $alias .= $option->isValueRequired() ? '=' : ' ';

            $completions[] = $alias;
        }
    }

    /**
     * @param string[] $completions
     * @param mixed[] $registeredValues {@see CliRequestProcessor::getInnermostBranchRegisteredValues()}
     */
    private function completeParamValue(
        array &$completions,
        array $registeredValues,
        string $enteredValue,
        ParameterAbstract $param,
        string $prefix = '',
    ): void {
        $parameterRegisteredValues = $registeredValues[$param->getName()] ?? [];
        if ($parameterRegisteredValues && !$param->isArray()) {
            return;
        }

        foreach ($param->complete($enteredValue) as $line) {
            if (in_array($line, $parameterRegisteredValues)) {
                continue;
            }
            $completions[] = $prefix . $line . ' ';
        }
    }

    /**
     * Tailors list of completion strings to the prefix which is being completed.
     *
     * @param string[] $completions
     * @return string[]
     */
    private function getCompletionsLimitedByToken(array $completions, ?Token $arg, string $compWordBreaks): array {
        if (!$arg) {
            return $completions;
        }

        $cmpWord = mb_strtolower($arg->word);
        $cmpArg  = mb_strtolower($arg->arg);
        $length  = mb_strlen($cmpArg);

        /*
         * Readline treats our completion options as variants of last comp-word.
         * Those words are separated not by IFS, like shell-words, but by COMP_WORDBREAKS characters like '=' and ':'.
         *
         * Our tokenizer splits its words in a shell-word manner, therefore the completion options can contain
         * many comp-words. For correct completion to work, we need to find the last word break and remove everything
         * before it from our options, leaving only the last comp-word.
         */
        $prefix      = $arg->getArgTail($compWordBreaks);
        $forcePrefix = ($prefix != $arg->arg);

        foreach ($completions as $key => $variant) {
            // Need to convert the casing (ac<tab> -> aCC).
            $variantPrefix = mb_strtolower(mb_substr($variant, 0, $length));
            if ($variantPrefix != $cmpWord || mb_strlen($variant) == $length) {
                // Does not match or is equal to what is being completed; skip this option.
                unset($completions[$key]);

                continue;
            }

            /*
             * If the arg matches the word (that is, there is no special syntax in the arg)
             * and we don't have to force the prefix because of a word break,
             * then it's better to use the whole variant string instead of a prefixed one
             * (this way we can get correct case of chars).
             */
            if ($forcePrefix || $variantPrefix != $cmpArg) {
                $completions[$key] = $prefix . mb_substr($variant, $length);
            }
        }

        return $completions;
    }

    /**
     * @param mixed[]|null $args
     */
    public static function executeAutocomplete(Config $config, ?array $args = null): void {
        if (null === $args) {
            $args = $_SERVER['argv'];
        }

        $compWordBreaks = end($args);
        $compPoint      = (int) prev($args);
        $compLine       = prev($args);

        $completion = new self($config);
        foreach ($completion->complete($compLine, $compPoint, $compWordBreaks) as $variant) {
            echo $variant . PHP_EOL;
        }
    }

    public static function generateAutocompleteScript(string $shellAlias, ?string $scriptPath = null): string {
        if (null === $scriptPath) {
            $scriptPath = static::getScriptFilename();
        } else {
            $scriptPath = trim($scriptPath);
            if ('' === $scriptPath || false === realpath($scriptPath)) {
                throw new RuntimeException("Invalid script path " . var_export($scriptPath, true));
            }
        }

        // If the file has a shebang, we assume it can execute itself.
        if (is_readable($scriptPath) && file_get_contents(filename: $scriptPath, length: 2) == '#!') {
            $shellAliasCommand   = $scriptPath;
            $autocompleteCommand = escapeshellarg($scriptPath);
        } else {
            $shellAliasCommand   = static::getPhpCommand(true) . ' ' . escapeshellarg($scriptPath);
            $autocompleteCommand = $shellAliasCommand;
        }

        $shellAliasEscaped = escapeshellarg($shellAlias);
        $shellFunctionName = '_parametizer-autocomplete_' . $shellAlias;

        return "alias {$shellAliasEscaped}=" . escapeshellarg($shellAliasCommand) . PHP_EOL
            . "function {$shellFunctionName}() {" . PHP_EOL
            . '    saveIFS=$IFS' . PHP_EOL
            . '    IFS=$\'\n\'' . PHP_EOL
            . '    COMPREPLY=($(' . $autocompleteCommand . ' --' . Config::OPTION_NAME_AUTOCOMPLETE_EXECUTE
            . ' "$COMP_LINE" "$COMP_POINT" "$COMP_WORDBREAKS"))' . PHP_EOL
            . '    IFS=$saveIFS' . PHP_EOL
            . '}' . PHP_EOL
            . "complete -o bashdefault -o default -o nospace -F {$shellFunctionName} {$shellAliasEscaped}" . PHP_EOL
            . PHP_EOL;
    }

    /**
     * @return false|string
     */
    public static function getScriptFilename() {
        return realpath($_SERVER['PHP_SELF']);
    }

    /**
     * Returns a command to run the PHP CLI.
     *
     * 'Command' means it is already escaped, while 'filename' is not.
     */
    public static function getPhpCommand(bool $assumeNoShebang = false): string {
        // Weird magic, but well, there's no way to do this right (right?).

        // If we're a nice shell script, let's use that.
        if (!$assumeNoShebang) {
            $scriptPath = static::getScriptFilename();
            if (is_readable($scriptPath)) {
                [$line] = explode(PHP_EOL, file_get_contents($scriptPath, false, null, 0, 1024), 2);
                if (str_starts_with($line, '#!')) {
                    return mb_substr($line, 2);
                }
            }
        }

        // A convenient constant in PHP.
        return escapeshellarg(PHP_BINARY);
    }
}
