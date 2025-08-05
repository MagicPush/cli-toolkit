# [CliToolkit](../README.md) -> TODO

The list of plans and ideas for future development.

## Contents

- [Baseline](#baseline)
- [Class-based scripts as subcommands](#class-based-scripts-as-subcommands)
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
1. Move most [HelpGenerator.php](../src/Parametizer/Config/HelpGenerator.php) constants (where relevant)
   to [EnvironmentConfig.php](../src/Parametizer/EnvironmentConfig.php).
1. PHPUnit: Try messing with the coverage - make tests call test scripts inside the same processes with test methods.
    1. Consider adding DI-methods like `logOutput()` and `logError()`, which may be related to actual STD* streams,
       files or any other kinds of streams.
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
    1. `--help=more` shows hidden parameters (any visibility mask) like internal completion-related
   parameters.

   </details>

## Class-based scripts as subcommands

<details>
<summary>Points to consider</summary>

1. - [ ] [features-manual.md](features-manual.md):
    1. - [x] A comparison table between "plain scripts" and "classes".
        1. - [x] Start with a "summary" paragraph.
    1. - [ ] Launcher performance. Describe possible approaches (including the built-in caching mechanism).
        1. - [ ] Make a link to this from the comparison table.
    1. - [ ] Built-in subcommands.
        1. - [ ] `list` as a default value.
             No other parameters are processed correctly unless `list` is specified explicitly.
    1. - [ ] `ConfigBuilder::shortDescription()`
    1. - [ ] [run.php](../tools/cli-toolkit/run.php)
        1. - [ ] [ScriptClassDetector.php](../src/Parametizer/ScriptDetector/ScriptClassDetector.php)
        1. - [ ] [execute-class.php](../tools/cli-toolkit/execute-class.php)
        1. - [ ] Available subcommands.
    1. - [ ] [Question.php](../src/Question/Question.php)

         Also describe mass script generator as a useful tool to "play around" with the library.
    1. - [ ] Update comments generated in
         [LauncherSkeleton.php](../tools/cli-toolkit/ScriptClasses/Generate/LauncherSkeleton.php)
         with links to the manual.
1. - [ ] BONUS TASKS:
    1. - [ ] Create a document about values and / or goals of the library.
        1. - [ ] Ease scripts development and maintenance. Even if the library code deep inside is or will become
             notably complex.
        1. - [ ] Zero or minimal set of dependencies - to simplify the process of updating the library, to improve
             the library components' performance (less universal approach -> faster processing).
             Even if I have to implement solutions that have been already developed in some other open-source libraries.
    1. - [ ] Support single-named aliases: `cli-toolkit:generate:completion-script` is the "main" name for
         a script, that may be also called via `gas` or `generate-completion` aliases.

         ... Or try making a subcommand alias within a completion script.
       
         Some ideas:
       
        * The most difficult part: make `Parser` detect an alias in some "odd" substring, then find a corresponding
          "main name" via a "lookup" array in a `Config`.
        * Keep aliases in a `Config` separated (not as additional "branches"), then add along with "branch" keys into
          `allowedValues()`. Then both validation and completion will consider aliases too.
        * Uniqueness: aliases should be kept like `(string) alias => (string) main subcommand name`, but there also
          should be a simple (quick) enough way to convert such an array into
          `(string) main subcommand name => (array|string[]) list of aliases`
        * Think how to set those aliases in a comfort (for users) way.
          
          As for now, I see it only as the third parameter for `newSubcommandSwitch()` as an array of aliases. Then you
          may specify the "main name" and aliases next to each other by calling the method with named parameters.
    1. - [ ] Composer post install message with the generator launch command.
        * See https://getcomposer.org/doc/articles/scripts.md
    1. - [ ] Support positioned headered groups for subcommands (like `Built-in:`).

         A possible implementation:

         ```php
         // ConfigBuilder...
             // empty group
             ->newSubcommand('something', Parametizer::newConfig() ...)
             ->newSubcommand('other-thing', Parametizer::newConfig() ...)
             ...

             ->newSubcommandGroup('Database tools')
             ->newSubcommand('restart', Parametizer::newConfig() ...)
             ->newSubcommand('update-schema', Parametizer::newConfig() ...)
             ...

             ->newSubcommandGroup('Special tools')
             ->newSubcommand('order-tea', Parametizer::newConfig() ...)
             ->newSubcommand('buy-cookies', Parametizer::newConfig() ...)
             ...
         ```

        1. [ ] Test:
            1. [ ] `--` is not shown if there is no zero-section scripts.
            1. [ ] No subgroups by name sections are created in headered groups.
            1. [ ] Headered group names are NOT sorted. But auto-group names (based on name sections) ARE sorted.
            1. [ ] In `slim` mode all subcommands are sorted within groups only,
               where "auto" is considered as a single group.
1. - [x] FINISHING MOVES:
    1. - [x] Renaming, moving and other trivial refactoring:
        1. - [x] `../src/Parametizer/Script` -> `.../ScriptClass`
        1. - [x] (optionally) `ScriptAbstract` -> `ScriptClassAbstract`
        1. - [x] `Autocompletion*` -> `Completion*`, `auto[-]complet*` -> `complet*`
        1. - [x] `Autocompletion` directory -> `Completion`
        1. - [x] `tools/cli-toolkit/launcher.php` -> `cli-toolkit.php` / `toolbox.php` / etc.
        1. - [x] `../src/ToolBelt` -> `.../Utils`
        1. - [x] Move [CliToolkitScripts](../tests/Tests/Tools/CliToolkitScripts) tests in separate subdirectories.
        1. - [x] Make all test classes `final` (where possible).
    1. - [ ] ~~See if `Parametizer::newConfig()` internal call chain may (and should) be
       simplified - if a config with 'env' might be created ASAP.~~

       Env config may be created automatically earlier, and `Config` may require an env config instance only (no extra
       flag). However, "no exception" flag could be useful along the way in the future, otherwise one more backward
       incompatibility could appear in the future.

       On a development level, it changes almost nothing - almost no calls become easier in 90% of cases, if
       the methods' signatures are changed.
    1. - [x] Try easing `ScriptClassAbstract::getConfigBuilder()` declaration. Consider:

        - generating an empty `ConfigBuilder` instance "automatically" (mainly for temp scripts);
        - ~~making `getConfigBuilder()` non-static, creating `ConfigBuilder` instance inside `__construct()`.~~
    1. - [x] Consider adding even more [backward incompatibilities](todo.md#next-major-release) ~~or delaying
       the next major release, see [already implemented backward incompatibilities](changelog.md#v300)~~.

</details>

<details>
<summary>Stuff implemented</summary>

1. - [x] Support composite names: 2 parts at least - `section:script` (like in Symfony).
     Single named scripts should be allowed too.
    1. - [x] Also try to allow any amount of parts in a script full name (3, 4, ..., N).
1. - [x] Remove now obsolete "plain" scripts from `tools/cli-toolkit`
     or replace repeating code with script classes usages.
1. - [x] Add a built-in subcommand `list` to list all detected scripts with their names and short descriptions.
     Also consider this:
    1. - [x] Update
         [GenerateMassTestScripts.php](../tools/cli-toolkit/ScriptClasses/Internal/GenerateMassTestScripts.php)
         by adding name sections.
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
1. - [x] Refactoring stage:
    1. - [x] Rename [utils](../tests/utils) to `Utils` (directory and namespace).
    1. - [x] Apply `TestUtils::newConfig()` in all test scripts.
    1. - [x] Remove `@noinspection SpellCheckingInspection` where possible
         by replacing substrings with "more typo friendly".
1. - [x] Make `list` as the default value for a subcommand switch.
1. - [x] Ensure a parent config parameter is not shadowed by a subcommand name as a key for
     a subcommand request subarray.

     Example: add `list` argument to a launcher and lose it's value after `CliRequestProcessor` replaces it with
     `list` subcommand branch request.
1. - [x] Add manual short description support - in case automatic short description is not so good.
    1. - [x] Add a short description to built-in subcommands where needed.
1. - [x] Add `ScriptClassLauncher` to keep all launchers common code.
    * [ScriptClassDetector.php](../src/Parametizer/ScriptDetector/ScriptClassDetector.php) may be created
      by default with a single search path `__DIR__` and its own path as an exception.
1. - [x] Support `EnvironmentConfig` setup:
    1. - [x] ~~See if `$_SERVER` may be used instead of `debug_backtrace()`.~~
    1. - [x] A script class skeleton should support a method to set an `EnvironmentConfig` instance received from
         a script launcher or (otherwise) created from scratch (including the config file autoloader).
        * ~~If an `EnvironmentConfig` instance is passed from a launcher to a script class, it should be treated
          as a default config (not a forced only-config) - a script class should be able to _update_ parameters.~~
    1. - [x] ~~Think about the load priorities: a) launcher env config instance,
         b) script class subtree config files.~~
    1. - [x] ~~Try easing `ScriptClassAbstract::getConfigBuilder()` declaration, consider making an empty `ConfigBuilder`
         instance "automatically" by making `getConfigBuilder()` non-static or in a separate method.~~
1. - [x] Make `newSubcommandSwitch()` optional.

     Only a single subcommand switch is possible, so there is no need to specify its name explicitly
     (but it's still should be possible if customization is preferred).

     Also, rename  throughout the whole project:

    1. - [x] "subcommandValue" to "subcommandName".
    1. - [x] "subcommand value" to "subcommand name".
    1. - [x] "subcommandSwitchValue" to "subcommandName".
1. - [x] Test performance on many files.
    1. - [x] Create test classes generator to generate lost of class-based scripts.
    1. - [x] Compare file tokenizer vs regexp.
        * Tokenizer works 20% slower, same memory usage. Replaced with regexp.
    1. - [x] A generated launcher should also show time elapsed and RAM usage.
    1. - [x] ~~Remove
         [GenerateMassTestScripts.php](../tools/cli-toolkit/ScriptClasses/Internal/GenerateMassTestScripts.php)
         from the launcher, make it not detectable by
         [CompletionScript.php](../tools/cli-toolkit/ScriptClasses/Generate/CompletionScript.php).~~
    1. - [x] Try removing script name parts and subcommand name regexp validations. Think if caching is needed.
    1. - [x] Consider adding optional caching in
         [ScriptClassDetector.php](../src/Parametizer/ScriptDetector/ScriptClassDetector.php).

        * Searching in large projects (~ 5GB) may last for 30+ seconds!
    1. - [x] Test `EnvironmentConfig` config autoload performance with lots (1K+) of files.
1. - [x] TEST
    1. - [x] [ScriptClassDetector.php](../src/Parametizer/ScriptDetector/ScriptClassDetector.php):
        1. - [x] Base script classes detection.
        1. - [x] Consider a case: script classes are spread all over a huge project. The only search path is
             the huge project's root directory. A full scan may take a while.

             Consider caching:

            * ~~by a setting and/or based on all scanned files count;~~
            * ~~possible automatic invalidation condition;~~
            * easy to use manual cache clear tool.
        1. - [x] No abstract classes are detected ~~and (if possible) loaded into memory~~.
        1. - [x] Final classes are detected too.
        1. - [x] Classes without namespaces are detected too.
        1. - [x] Several search paths.
        1. - [x] Exclude (black-list) exact paths ~~or parts of~~.
        1. - [x] ~~Force-include (white-over-black) parts of paths.~~
        1. - [x] ~~Force-include (white-over-black) exact paths.~~
        1. - [x] Invalid / not readable paths.
        1. - [x] Names are naturally sorted (`script2` is placed above `script10`).
        1. - [x] Do not process duplicate paths (local vs real paths).
    1. - [x] [ScriptClassLauncher.php](../src/Parametizer/ScriptClass/ScriptClassLauncher/ScriptClassLauncher.php)
        1. - [x] Defaults in the constructor: a detector (with caching DISabled) and a config.
    1. - [x] [ScriptClassAbstract.php](../src/Parametizer/ScriptClass/BuiltinSubcommand/ScriptAbstract.php)
        1. - [x] Simple and composite names (with sections).
        1. - [x] `getScriptInnerName()` must not be empty.
        1. - [x] `getScriptInnerName()` auto name generation:
             `name`, `Name`, `SomeName`, `PDF`, `SomeNamePDF`, `PDFSomeName`, `SomePDFName`
    1. - [x] [cli-toolkit](../tools/cli-toolkit)
        1. - [x] [CompletionScript.php](../tools/cli-toolkit/ScriptClasses/Generate/CompletionScript.php)

             Functional tests that look for substrings in generated files.
        1. - [x] [EnvironmentConfigFile.php](../tools/cli-toolkit/ScriptClasses/Generate/EnvironmentConfigFile.php)

             Just assert generated file's contents.
1. - [x] Provide a docker config / build script for tests. And rewrite tests.

     Before that the tests are environment-dependent:

    1. PHP "development" config setup causes exceptions printed in `STDOUT` instead of `STDERR`.
    1. Tests are launched under `root`, so file permission-related tests fail.
    1. `posix_isatty()` / `stream_isatty()` always return false in a container launched from PhpStorm.
1. - [x] Add an alternate script detector.
    1. - [x] Detects plain Parametizer-based scripts (just move there `CompletionScript` current logic).
    1. - [ ] ~~Regular plain scripts.~~
    1. - [x] Forbid duplicate script names - throw an exception or silently skip duplicates.
        1. - [ ] ~~Invent a mean to generate unique alternative names for duplicates.~~
    1. - [x] Test (at least, manually) the future skeleton scenarios:
        1. Include everything except [tests](../tests) and [cli-toolkit](../tools/cli-toolkit).
        1. Include some directories recursively plus the current one (the skeleton launcher location)
           non-recursively.
    1. - [x] Replace internal detection in
         [CompletionScript.php](../tools/cli-toolkit/ScriptClasses/Generate/CompletionScript.php)
         with the created detector class.
1. - [x] Add a simple script to execute any class script without using a detector.
1. - [x] Always hide built-in and
     [ClearCache.php](../src/Parametizer/ScriptClass/ScriptClassLauncher/Subcommand/ClearCache/ClearCache.php)
     subcommands from [ScriptClassDetector.php](../src/Parametizer/ScriptDetector/ScriptClassDetector.php) instances
     with any setup. Otherwise the "whole project" detection setup causes an exception while trying to include
     the "detected" `ClearCache` subcommand without its context object.
    * Implement a method ~~or a constant~~ as a boolean answer like "is a hidden subcommand".
    * Cover with an autotest.
    * ~~Try hiding `cli-toolkit:internal:`, but "sometimes" making it available again~~ (see possible options):
        * In [run.php](../tools/cli-toolkit/run.php) only.
        * Only if a launcher (any - considering the script class is detectable) is called within the library solely,
          not within some other project that includes this library.
          For instance, check if `.git` directory exists in the library root directory.
        * [EnvironmentConfig.php](../src/Parametizer/EnvironmentConfig.php) new option.
        * Existence of a particular file in the library `/local/` directory.
1. - [x] Place [ClearCache.php](../src/Parametizer/ScriptClass/ScriptClassLauncher/Subcommand/ClearCache/ClearCache.php)
     command for [ListScript.php](../src/Parametizer/ScriptClass/BuiltinSubcommand/ListScript.php)
     in its own uniquely headered section.
    1. [x] Test `ClearCache` is placed in a headered group.
    1. [x] Test `ClearCache` subcommand always utilizes its parent environment config.
1. - [x] [ScriptClassLauncher.php](../src/Parametizer/ScriptClass/ScriptClassLauncher/ScriptClassLauncher.php):
    1. - [x] Replace `->searchDirectory(dirname($_SERVER['SCRIPT_FILENAME']));` with something else: the current
         default value does not work properly if a launcher is located in some distant directory.
         Consider any / some of these options:

        1. - [ ] ~~Detect the "main project" directory path - the same directory where "the highest `vendor`"
             directory is located.~~ Will be done in a skeleton generator.
        1. - [x] Set a particular directory in the upcoming skeleton generator's result. It might be
             the "main project" directory path or any particular directory set up in the skeleton generator.
        1. - [x] Remove the default detector - force the library users always to set up a detector manually.
    1. - [x] Decide if detectors should throw exceptions by default.
         Connected with the default state of `throwOnException()` and setter methods.

        1. - [x] Describe the choice of default values somewhere, so you will not forget the reasons.
1. - [x] "First steps" skeleton generator for script classes launching.
    1. - [x] Add the generator itself.

         Something that will help users to start using the library quickly and easily. For instance, it should
         create a launcher with some default detection (no cache), maybe add a completion script right away,
         maybe generate a blank script class, etc.
    1. - [x] Test it.
    1. - [x] [README.md](../README.md), describe how to generate a skeleton (in a form of a "quick start").
    1. - [x] Fill `TODO` placeholder in
         [development-notes.md](development-notes.md#throwing-or-ignoring-exceptions-default-policy).
</details>

## Next major release

Let's try making major releases less frequent by accumulating here all ideas with backward incompatibilities.
When the time comes, the whole bunch of stuff mentioned here will be implemented in a single major version.

1. Move to PHP 8.4 as a minimal required version. This includes:
    1. Replace `*trim()` functions with `mb_*trim()` alternatives.
    1. Replace `mb_strtoupper(mb_substr($pathComponent, 0, 1)) . mb_substr($pathComponent, 1)` in
       [LauncherSkeleton.php](../tools/cli-toolkit/ScriptClasses/Generate/LauncherSkeleton.php)
       with `mb_ucfirst($pathComponent)`

## Just fun thoughts to (maybe) implement one day

1. Support multiline input for `Question::ask()`.

   Consider a special mode when `getInput()` method is being called indefinitely until a special substring is met
   to indicate the end of input.

    * Add an option to concatenate multiline input with a specified symbol. This way you may decide if input parts
    are "glued" by a space character, by a new line character, by some other substring or even by no substring at all.
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
1. Fix the completion "bug" case: with `-o1<tab>` we expect the modified line `-o100`,
   but get `100` (`-o` is vanished).
    * Reason: `$COMP_WORDBREAKS` shell variable is considered (not `Completion::COMP_WORDBREAKS`), bash-completion
      sets the cursor after the last word break (` ` before `-o`), so the rest (`-o`) is trimmed.
    * Possible, but odd solution: alter `$COMP_WORDBREAKS` shell variable during runtime (append an option short name),
      then restore the variable's original value right before a script is terminated.
1. Progress bar.
1. - [ ] Implement a "typo guesser" like in `composer`:

     ```
     $ composer lizstz

     Command "lizstz" is not defined.

     Do you want to run "list" instead?  (yes/no) [no]:
     >
     ```
1. - [ ] Detected script names may be accessed as subcommand names by specifying their full names
     (completion-powered) or unambiguous first characters substrings (like in Symfony console) - if there are
     scripts `clear-cache` and `clone-config`, the unambiguous enough substrings are `cle` and `clo`
     respectively.
    1. - [ ] In case of composite names each name substring should be mentioned - for
         `cli-toolkit:generate:completion-script` you should specify `c:g:a`
         (if it is unambiguous enough - there are no other scripts named `c*:g*:a*`).
    1. - [ ] Support showing minimum unambiguous shortcuts via the runner list command
         (switched on/off by a flag option).
