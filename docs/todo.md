# [CliToolkit](../README.md) -> TODO

The list of plans and ideas for future development.

## Baseline

1. Docs:
    1. Array parameters (especially for `newArrayArgument()`).
    1. Validators custom exception messages.
    1. Details about Parametizer builder methods
       (smart indent in `description`, "allowed values" types (or completion only), required options, etc.).
1. Try out the parameters ambiguity puzzle: `-fctest`, where `-f` is a flag, `-c` is an option and there is
   also a `-t` flag. Possible outcomes:
    1. `test` is the `-c` value, `-t` flag is not enabled.
    1. `tes` is the `-c` value and `t` is the `-t` flag being enabled.
1. HelpGenerator: show the same script path as used for calling it - by alias or by relative path.
1. (if possible) Auto-tests for [Question.php](../src/Question/Question.php).
1. [Question.php](../src/Question/Question.php): add a demo script showing different types of questions.
1. Flag+value combined options (`<no mention> | --verbose | --verbose=more`).

   <details>
   <summary>More details</summary>

   Possible states:
    * A parameter is not mentioned: the value is `null` or `false`.
    * A parameter is mentioned as a flag (no specific value): the value is `true` or some default.
    * A parameter is mentioned with a value.

   See also [symfony implementation](https://symfony.com/doc/current/console/input.html#options-with-optional-arguments)
   as an example.

   Points to consider:
    1. Solve the ambiguity:
        * For `-vo` always consider `-v` as an ordinal option (unless it is a flag-only option)
          and `o` as a value for `-v`.
          If `-v` is flag-only, then `o` should be a flag-like (a flag-only or a flag-or-option).
        * `-vv` should not be considered as the same flag mentioned twice (unless it is a flag-only option).
          It is an option `-v` with a value `v`.
        * For `-v more` consider `more` as a value for `-v` (unless `-v` is a flag-only option).
          If you want to pass `more` as an argument value and use flag-or-option `-v` as a flag, specify a double dash:
          `-v -- more`
    1. Show explicitly such an option type on a generated help page.

   Subtasks:
    1. `--help=more` shows hidden parameters (any visibility mask) like internal autocomplete-related
   parameters.

   </details>

## Large feature ideas

1. Environment Config - for setting up Parametizer behavior:
    <details>
    <summary>Points to consider</summary>

    1. [x] Set config automatically via a JSON config file.
    1. [ ] Type-related and custom validation for settings.
       Test:
        1. JSON type validation for all settings.
            1. Make one of `int` settings value as a string
               in `tests/Tests/Parametizer/EnvironmentConfig/autoload-files/parametizer.env.json`
        1. JSON type validation example for `EnvironmentConfig::$helpGeneratorShortDescriptionChars*`:
           allow `20` and `'20'`, but not `20.0` or `'20.0'`
    1. [ ] A default config generator with all settings and descriptions.
    1. [ ] Docs.
    1. [x] Specify `$throwOnException` for all test scripts.
    1. [ ] Think about adding other possible backward incompatibilities. Additional sublist:
        1. [ ] Rename `Parametizer::newConfig()` into `build()` / `startBuilding()` / `createBuilder()` / etc.
        1. [ ] In Parametizer::setExceptionHandlerForParsing() output `ParseErrorException` into `STDERR`,
           simplify the code.
    1. [ ] Refactoring:
        1. [ ] `declare(strict_types=1);` on a new line for all php-files.
    1. [ ] Review [changelog](changelog.md#v200), especially 'only if `DEV`' paragraph.

    </details>

    <details>
    <summary>Settings to implement</summary>

    1. A short name for built-in `--help` option.
        * It should be possible to specify no short name at all (also, a default state).
        * If there is no reasonable way to set a short name without walking recursively through all subcommand branches,
          implement it as a pre-setup-only setting (must be specified in a Parametizer config constructor).
    1. Values for `HelpGenerator::SHORT_DESCRIPTION_MIN_CHARS` and `SHORT_DESCRIPTION_MAX_CHARS`.
    </details>
1. Class-based scripts as subcommands (Symfony-like).
    <details>
    <summary>Points to consider</summary>

    1. Support `EnvironmentConfig` setting:
        1. A script class skeleton should support a method to set an `EnvironmentConfig` instance created from scratch
           or received from a script launcher.
        1. The skeleton should support `Parametizer::newConfig()` alternative that includes a parent `EnvironmentConfig`
           instance, but also allows to set a new instance.
    1. Support different script (subcommand) naming.
        * Composite names: 2 parts at least - `section:script` (like in Symfony).
          Single named scripts should be allowed too.

          Also try to allow compositions of implement an ability to use any amount of parts (3, 4, ..., N).
        * Support single-named aliases: `cli-toolkit:generate-autocompletion-scripts` is the "main" name for a script,
          that may be also called via `gas` or `generate-completion` aliases.
        * Ensure no names and aliases duplication.
    1. Add built-in subcommand to list all detected scripts with their names and short descriptions.
    1. Detected script names may be accessed as subcommand values by specifying their full names (autocomplete-powered)
       or unambiguous first characters substrings (like in Symfony console) - if there are scripts `clear-cache`
       and `clone-config`, the unambiguous enough substrings are `cle` and `clo` respectively.
        * (like in Symfony) In case of composite names each name substring should be mentioned - for
          `cli-toolkit:generate-autocompletion-scripts` you should specify `c:g`
          (if it is unambiguous enough - there are no other scripts named `c*:g*`).
        * Support showing all available script names via the runner list command (switched on/off by a flag option).
    1. Add a scripts launcher generator that initially stores a path to the CliToolkit engine.

       In future, there may also be a path to a settings config file (see the "_Environment Config_" feature below)
       or the config contents itself.
    1. Scripts launcher may detect ordinal Parametizer-based scripts
       (one of the launcher / "_Environment Config_" config settings).

       Thoughts about such scripts naming:
        * Generate default names by minimal unambiguous paths.
        * Add a Parametizer config option to set a script name (and aliases). Use it as a way to detect such scripts
          and add those to a launcher available commands list.

    </details>
1. A web interface for foreground / background scripts launch. Includes indications / notifications
   for finished (successfully or not) and halted (which require input from a user) scripts.

   The web interface should be used as an example only - you may replace with with your own web or console interface.
   The main point is in the machinery behind the interface that you can reuse.

## Next major release

Let's try making major releases less frequent by accumulating here all ideas with backward incompatibilities.
When the time comes, the whole bunch of stuff mentioned here will be implemented in a single major version.

1. Naming:
    1. `CliRequest::getCommandRequest()` -> `getSubcommandRequest()`
    1. `CliRequestProcessor::getAllowedArguments()` -> `getInnermostBranchAllowedArguments()`
    1. `CliRequestProcessor::append()` -> `appendToInnermostBranch()`
1. Make `HelpGenerator::getUsageTemplate()` protected and non-static.
    * Then replace `HelpFormatter::createForStdOut()` call inside the method with `$this->formatter`.
1. Move to PHP 8.3 as a minimal required version. This includes:
    1. Replace `mb_str_pad` polyfill with native `mb_str_pad`.
    1. Update PHPUnit. And try messing with the coverage.
1. Move to PHP 8.4 as a minimal required version. This includes:
    1. Replace `*trim()` functions with `mb_*trim()` alternatives.

## Just fun thoughts to (maybe) implement one day

1. Fix the autocompletion "bug" case: with `-o1<tab>` we expect the modified line `-o100`,
   but get `100` (`-o` is vanished).
    * Reason: `$COMP_WORDBREAKS` shell variable is considered (not `Completion::COMP_WORDBREAKS`), bash-completion
      sets the cursor after the last word break (` ` before `-o`), so the rest (`-o`) is trimmed.
    * Possible, but odd solution: alter `$COMP_WORDBREAKS` shell variable during runtime (append an option short name),
      then restore the variable's original value right before a script is terminated.
1. Complex validators for grouped or dependent parameters.

   As for now, validators are fired only within connected parameters.

   It would be cool to be able to validate a parameter "B" based on the pre-validated value of a parameter "A".
   Also if a validation exception happens, the generated help page should include all affected parameters
   ("A" and "B").
1. Simplify outputs strings formatting ([TerminalFormatter](../src/TerminalFormatter.php)) with something like tags.

   <details>
   <summary>More details</summary>

   Something like `"value: '<itemValue>{$value}</itemValue>'"` instead of
   `"value: '" . $errorFormatter->itemValue($value) . "'"`.
   See also [symfony coloring](https://symfony.com/doc/current/console/coloring.html) as an example.

   Points to consider:
    * If formatting is disabled, the tags should be stripped from strings before outputting.
    * Ignore (for formatting or stripping) not supported tags.
    * Create a mean to escape a tag - to output it as is (for instance, as a formatting example).
    * Use this feature to improve current built-in formatting - to simplify and shorten the code.
   </details>
1. Symfony-like (or not like) progress bar.
