# [CliToolkit](../README.md) -> TODO

The list of plans and ideas for future development.

## Contents

- [Baseline](#baseline)
- [Large feature ideas](#large-feature-ideas)
- [Next major release](#next-major-release)
- [Just fun thoughts to (maybe) implement one day](#just-fun-thoughts-to-maybe-implement-one-day)

## Baseline

1. Add tests:
    1. `VariableBuilderAbstract::completionCallback()`
    1. `BuilderAbstract::visibilityBitmask()`
1. Docs:
    1. Array parameters (especially for `newArrayArgument()`).
    1. Validators custom exception messages.
    1. Details about Parametizer builder methods
       (smart indent in `description`, "allowed values" types (or completion only), required options, etc.).
1. Make `newSubcommandSwitch()` optional.
    * Only a single subcommand switch is possible, so there is no need to specify its name explicitly
      (but it's still should be possible).
    * Rename `$subcommandName` to `$subcommandSwitchName`.
    * Rename "subcommand value" to "subcommand name" throughout the whole project.
1. Support a config file for
   [GenerateAutocompletionScript.php](../tools/cli-toolkit/Scripts/GenerateAutocompletionScript.php). It will ease
   specifying the script settings for multiple launches.
1. PHPUnit: remove the PHAR from the project completely, try OS-based installation.
1. PHPUnit: Try messing with the coverage - make tests call test scripts inside the same processes with test methods.
    1. Consider adding DI-methods like `logOutput()` and `logError()`, which may be related to actual STD* streams,
       files or any other kinds of streams.
        * This could be useful in the "An interface for foreground / background scripts launch" concept
          (see [Large feature ideas](#large-feature-ideas)).
        * Think about config types where to store stream sources: `EnvironmentConfig` or a new config type like
          a "runtime config".
          
          Consider a case: normally a script utilizes STD* streams. But when launched in background,
          this script should write output and error strings into files.
    1. Try to cover formatting in tests.
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

1. Class-based scripts as subcommands (Symfony-like).
    <details>
    <summary>Points to consider</summary>

    1. TEST
        1. - [ ] Subcommands detector:
            1. - [ ] Detection:
                1. - [ ] Script classes.
                1. - [ ] Plain Parametizer-based scripts.
                1. - [ ] Regular plain scripts.
            1. - [ ] There may be no namespace.
            1. - [ ] No abstract classes detected.
            1. - [ ] Final classes are detected too.
            1. - [ ] Classes without namespace are detected too.
            1. - [ ] Several search paths.
            1. - [ ] Ignore (black) masked lists for search paths.
            1. - [ ] Include (white) masked lists for search paths.
            1. - [ ] Force-ignore (black-over-whitelist) exact paths.
            1. - [ ] Force-include (white-over-black) exact paths.
            1. - [ ] Names are naturally sorted (`script2` is placed above `script10`).
            1. - [ ] Invalid / not readable paths.
        1. - [ ] `ScriptAbstract`
            1. - [ ] Simple and composite names.
            1. - [ ] `getLocalName()` must not be empty.
            1. - [ ] `getLocalName()` auto name generation:
                 `name`, `Name`, `SomeName`, `PDF`, `SomeNamePDF`, `PDFSomeName`, `SomePDFName`
    1. - [ ] [features-manual.md](features-manual.md):
        1. - [ ] Built-in subcommands.
        1. - [ ] [ScriptAbstract.php](../src/Parametizer/Script/ScriptAbstract.php)
        1. - [ ] [ScriptDetector.php](../src/Parametizer/Script/ScriptDetector.php)
        1. - [ ] [launcher.php](../tools/cli-toolkit/launcher.php)
    1. - [x] Remove now obsolete "plain" scripts from `tools/cli-toolkit`
         or replace repeating code with script classes usages.
    1. - [ ] Test performance on many files.
        1. - [x] Create test classes generator to generate lost of class-based scripts.
        1. - [x] Compare file tokenizer vs regexp.
            * Tokenizer works 20% slower, same memory usage. Replaced with regexp.
        1. - [ ] Remove [GenerateMassTestScripts.php](../tools/cli-toolkit/Scripts/Internal/GenerateMassTestScripts.php)
             from the launcher, make it not detectable
             by [GenerateAutocompletionScript.php](../tools/cli-toolkit/Scripts/GenerateAutocompletionScript.php).
        1. - [ ] Try removing script name parts and subcommand name regexp validations. Think if caching is needed.
        1. - [ ] Think if [ScriptDetector.php](../src/Parametizer/Script/ScriptDetector.php) needs caching.
        1. - [ ] Test `EnvironmentConfig` search performance.
    1. - [x] Add a built-in subcommand `list` to list all detected scripts with their names and short descriptions.
         Also consider this:
        1. - [x] Update [GenerateMassTestScripts.php](../tools/cli-toolkit/Scripts/Internal/GenerateMassTestScripts.php)
             by adding name sections. ~~For instance, each subfolder scripts are extended from a subfolder abstract class
             with redefined `getNameSections()`.~~
        1. - [x] Add the command automatically for all configs with switches.
        1. - [x] Add filtering by a substring.
        1. - [x] Support different output formats.
        1. - [x] Modify `--help` callback for a script with subcommands: if there is more than X subcommands available,
             do not show the full list of subcommands with usages, mention `list` subcommand instead.
        1. - [x] Use the same mechanism to add `help` subcommand,
             e.g. `git help status` is the same as `git status --help`.
            * The `help` subcommand should be added automatically for each config with a subcommand.
            * Possible values are all available subcommand names for the same switch.
        1. - [x] Update [changelog.md](changelog.md)
    1. - [ ] Make `list` as the default value for a subcommand switch.
    1. - [ ] Move all [HelpGenerator.php](../src/Parametizer/Config/HelpGenerator.php) constants
         to [EnvironmentConfig.php](../src/Parametizer/EnvironmentConfig.php).
    1. - [ ] Add manual short description support - in case automatic short description is not so good.
        1. - [ ] Add a short description to built-in subcommands where needed.
    1. - [ ] Refactoring stage:
        1. - [ ] Rename [utils](../tests/utils) to `Utils` (directory and namespace).
        1. - [ ] Apply `TestUtils::newConfig()` in all test scripts.
        1. - [ ] Remove `@noinspection SpellCheckingInspection` where possible
             by replacing substrings with "more typo friendly".
    1. - [ ] Support different script (subcommand) naming.
        1. - [x] Composite names: 2 parts at least - `section:script` (like in Symfony).
             Single named scripts should be allowed too.
            1. - [x] Also try to allow any amount of parts in a script full name (3, 4, ..., N).
        1. - [ ] Support single-named aliases: `cli-toolkit:generate-autocompletion-scripts` is the "main" name for
             a script, that may be also called via `gas` or `generate-completion` aliases.
             
             ... Or try making a subcommand alias via an autocompletion script.
        1. - [ ] Ensure no names and aliases duplication.

             Should happen automatically, if all names are used as built-in subcommand names.
    1. - [ ] Additions to [ScriptDetector.php](../src/Parametizer/Script/ScriptDetector.php):
         1. - [ ] Different ways to include/exclude files and/or directories.
         1. - [ ] Consider a case: script classes are spread all over a huge project. The only search path is
              the huge project's root directory. A full scan may take a while.
              
              Consider caching:
             * by a setting and/or based on all scanned files count;
             * possible automatic invalidation condition
             * easy to use manual cache clear tool
    1. - [ ] Scripts launcher may detect ordinal Parametizer-based scripts
         (one of the launcher / "_Environment Config_" config settings).

         Thoughts about such scripts naming:
        * Generate default names by minimal unambiguous paths.
        * Add a Parametizer config option to set a script name (and aliases). Use it as a way to detect such scripts
         and add those to a launcher available commands list.
    1. - [ ] Support `EnvironmentConfig` setup:
        1. - [ ] See if `$_SERVER` may be used instead of `debug_backtrace()`.
        1. - [ ] A script class skeleton should support a method to set an `EnvironmentConfig` instance received from
             a script launcher or (otherwise) created from scratch (including the config file autoloader).
            * If an `EnvironmentConfig` instance is passed from a launcher to a script class, it should be treated
              as a default config (not a forced only-config) - a script class should be able to _update_ parameters.
        1. - [ ] A script class skeleton should be also able to load an `EnvironmentConfig` instance from config files.
            * Think about the load priorities: a) launcher env config instance, b) script class subtree config files.

       <details>
       <summary>Base class implementation idea</summary>

       ```php
        <?php

        declare(strict_types=1);

        abstract class ParametizerPoweredAbstract {
            protected BuilderInterface $builder;


            public function __construct(protected ?EnvironmentConfig $launcherEnvConfig = null) {
                $this->builder = Parametizer::newConfig($this->createEnvironmentConfig());
            }

            protected function createEnvironmentConfig(): EnvironmentConfig {
                // Start reading config files from the actual script classes directory.
                // 'x' should be calculated, but may be a hardcoded trace jumps count.
                $bottom = debug_backtrace()[last-x];

                // Also test if __DIR__ placed as a default property value is transformed into
                // a current instance class directory, not the base abstract class directory.

                $envConfig = EnvironmentConfig::createFromConfigsBottomUpHierarchy($bottom);

                if (isset($this->launcherEnvConfig)) {
                    // New method: fill only the settings not filled from config files.
                    // But do it once (or mark all fields filled from files),
                    // otherwise other attempts will overwrite all settings.
                    $envConfig->appendNotFilledFromFiles($this->launcherEnvConfig);
                }

                return $envConfig;
            }

            abstract public function configure(): void;

            abstract public function execute(CliRequest $request): void;
        }

       ```
       </details>

        1. - [ ] Try moving `ScriptAbstract::NAME_*` constants into `EnvironmentConfig`
        1. - [ ] Try easing `ScriptAbstract::getConfiguration()` declaration, consider making an empty `ConfigBuilder`
             instance "automatically" by making `getConfiguration()` non-static or in a separate method.
    1. - [ ] Implement a "typo guesser" like in `composer`:
         
         ```
         $ composer lizstz


         Command "lizstz" is not defined.
         
         
         Do you want to run "list" instead?  (yes/no) [no]:
         >
         ```
    1. - [ ] Detected script names may be accessed as subcommand values by specifying their full names
         (autocomplete-powered) or unambiguous first characters substrings (like in Symfony console) - if there are
         scripts `clear-cache` and `clone-config`, the unambiguous enough substrings are `cle` and `clo` respectively.
        1. - [ ] (like in Symfony) In case of composite names each name substring should be mentioned - for
             `cli-toolkit:generate-autocompletion-scripts` you should specify `c:g`
             (if it is unambiguous enough - there are no other scripts named `c*:g*`).
        1. - [ ] Support showing minimum unambiguous shortcuts via the runner list command
             (switched on/off by a flag option).
    1. - [ ] Add a scripts launcher generator that initially stores a path to the CliToolkit engine.
         
         In future, there may also be a path to a settings config file (see the "_Environment Config_" feature below)
         or the config contents itself.
    1. - [ ] Consider adding even more [backward incompatibilities](todo.md#next-major-release) or delaying
       the next major release, see [already implemented backward incompatibilities](changelog.md#v300).

    </details>
1. An interface for foreground / background scripts launch. Includes indications / notifications
   for finished (successfully or not) and halted (which require input from a user) scripts.

   The web interface should be used as an example only - you may replace with with your own web or console interface.
   The main point is in the machinery behind the interface that you can reuse.

## Next major release

Let's try making major releases less frequent by accumulating here all ideas with backward incompatibilities.
When the time comes, the whole bunch of stuff mentioned here will be implemented in a single major version.

1. Renaming:
    1. All `Config::OPTION_NAME_*` (or, at least, `OPTION_NAME_HELP`) -> `PARAMETER_NAME_*`.
    1. `Config::getParams()` -> `getParameters()`. And related methods and properties too.
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
