# [CliToolkit](../README.md) -> TODO

The list of plans and ideas for future development.

## Contents

- [Baseline](#baseline)
- [Next major release](#next-major-release)
- [Just fun thoughts to (maybe) implement one day](#just-fun-thoughts-to-maybe-implement-one-day)

## Baseline

1. Try out the parameters ambiguity puzzle: `-fctest`, where `-f` is a flag, `-c` is an option and there is
   also a `-t` flag. Possible outcomes:
    1. `test` is the `-c` value, `-t` flag is not enabled.
    1. `tes` is the `-c` value and `t` is the `-t` flag being enabled.
1. Add tests:
    1. `VariableBuilderAbstract::completionCallback()`
    1. `BuilderAbstract::visibilityBitmask()`
1. Document:
    1. Array parameters (especially for `newArrayArgument()`).
    1. Details about Parametizer builder methods
       (smart indent in `description`, "allowed values" types (or completion only), required options, etc.).
1. Support single-named aliases: `cli-toolkit:generate:completion-script` is the "main" name for
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
1. HelpGenerator: show the same script path as used for calling it - by alias or by relative path.
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
    * Nested formatting should work as intended: inner opening tag should _add_ (not replace) formatting, inner
      closing tag should disable exact inner formatting (keeping / restoring outer formatting).
    * Use this feature to improve current built-in formatting - to simplify and shorten the code.
   </details>
1. Fix the completion "bug" case: with `-o1<tab>` we expect the modified line `-o100`,
   but get `100` (`-o` is vanished).
    * Reason: `$COMP_WORDBREAKS` shell variable is considered (not `Completion::COMP_WORDBREAKS`), bash-completion
      sets the cursor after the last word break (` ` before `-o`), so the rest (`-o`) is trimmed.
    * Possible, but odd solution: alter `$COMP_WORDBREAKS` shell variable during runtime (append an option short name),
      then restore the variable's original value right before a script is terminated.
1. Implement a progress bar. :)
1. Implement a "typo guesser" like in `composer`:

   ```shell
   $ composer lists

   Command "lists" is not defined.

   Do you want to run "list" instead?  (yes/no) [no]:
   >
   ```

1. Support positioned headered groups for subcommands (like `Built-in:`).

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

    1. Test:
        1. `--` is not shown if there is no zero-section scripts.
        1. No subgroups by name sections are created in headered groups.
        1. Headered group names are NOT sorted. But auto-group names (based on name sections) ARE sorted.
        1. In `slim` mode all subcommands are sorted within groups only,
           where auto-group names are considered as a single group.
1. Testing:
    1. Add DI-methods like `logOutput()` and `logError()`, which may be related to actual STD* streams,
       files or any other kinds of streams.

       Use cases: background launch; launching scripts within PHPUnit process,
       so coverage would actually show something.

        1. `STDIN` should be replaceable as well.
        1. Think about config types where to store stream sources: `EnvironmentConfig` or a new config type like
          a "runtime config".

          Consider a case: normally a script utilizes STD* streams. But when launched in background,
          this script should write output and error strings into files.
    1. Try messing with the coverage - make tests call test scripts inside the same processes with test methods.
    1. Try to cover formatting in tests.
1. Detected script names may be accessed as subcommand names by specifying their full names
   (completion-powered) or unambiguous first characters substrings (like in Symfony console) - if there are
   scripts `clear-cache` and `clone-config`, the unambiguous enough substrings are `cle` and `clo`
   respectively.
    1. In case of composite names each name substring should be mentioned - for
       `cli-toolkit:generate:completion-script` you should specify `c:g:c`
       (if it is unambiguous enough - there are no other scripts named `c*:g*:c*`).
    1. Support showing minimum unambiguous shortcuts via the runner list command
       (switched on/off by a flag option).
