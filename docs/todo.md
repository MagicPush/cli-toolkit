# [CliToolkit](../README.md) -> TODO

The list of plans and ideas for future development.

## Baseline

1. Docs:
    1. Subcommands.
    1. Array parameters (especially for `newArrayArgument()`).
    1. Validators custom exception messages.
    1. Details about Parameterizer builder methods
       (smart indent in `description`, "allowed values" types (or completion only), required options, etc.).
1. Allow making subcommands optional and/or adding default values.
   Example: `git --version` does not require a subcommand to show the whole package version.
   
   Points to consider:
    * Cover `CliRequest::getCommandRequest()` with autotests.
1. Simplify outputs strings formatting ([TerminalFormatter](src/TerminalFormatter.php)) with something like tags.

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
1. Smart completion for mentioned options (not array = do not complete it twice).
1. Complex validators for grouped or dependent parameters.

   As for now, validators are fired only within connected parameters.

   It would be cool to be able to validate a parameter "B" based on the pre-validated value of a parameter "A".
   Also if a validation exception happens, the generated help page should include all affected parameters
   ("A" and "B").
1. HelpGenerator: show the same script path as used for calling it - by alias or by relative path.
1. [Question.php](src%2FQuestion%2FQuestion.php): add a demo script showing different types of questions.
1. (if possible) Auto-tests for [Question.php](src%2FQuestion%2FQuestion.php).
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

## Major and super ambitious ideas

1. Class-based scripts as subcommands.
    1. Add a scripts launcher generator (let you specify a path to your launcher JSON settings).
    1. Scripts launcher may detect ordinal Parametizer-based scripts (one of the launcher settings).
1. A web interface for foreground / background scripts launch. Includes indications / notifications
   for finished (successfully or not) and halted (which require input from a user) scripts.
   
   The web interface should be used as an example only - you may replace with with your own web or console interface.
   The main point is in the machinery behind the interface that you can reuse.

## After moving to PHP 8.3 as a minimal required version

1. Replace `mb_str_pad` polyfill with native `mb_str_pad`.

## Just fun thoughts to (maybe) implement one day

1. Symfony-like (or not like) progress bar.
