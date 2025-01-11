# [CliToolkit](../README.md) -> Changelog

This change log references the repository changes and releases, which respect [semantic versioning](https://semver.org).

## DEV

Currently in `dev` branch:

### New features

1. `CliRequest` provides `getParamAs*` helper methods for easier parameters' values type casting,
   see [Features Manual: Type casting from requests](features-manual.md#type-casting-from-requests).
1. Added `HelpGenerator::getShortDescription()` that is used to show short versions for subcommand descriptions,
when outputting a help page for a script with a list of available subcommands.

### Patching

1. Added a paragraph about subcommands into [Features Manual: Subcommands](features-manual.md#subcommands).
1. Formatted subcommand value in `HelpGenerator::getUsageTemplate()`, so subcommands would be more visible in the
'COMMANDS' section of a help page with a list of available subcommands.
1. Fixed autocompletion for option short names in subcommands.
1. Made autocompletion smarter: duplicate option and value mentioning is not completed.

## v1.0.0

The first official release. Mainly focused on Cliff improvements and some additions like:
- more transparent and convenient config builder;
- [generate-autocompletion-scripts.php](tools/cli-toolkit/generate-autocompletion-scripts.php)
  (eases autocompletion init);
- [TerminalFormatter](src/TerminalFormatter.php) (helps format the improved generated help pages);
- [Question](src/Question/Question.php) (helps implement interactive scripts);
- PHPUnit for autotests plus more autotests.
