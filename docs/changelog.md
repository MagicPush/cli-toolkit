# [CliToolkit](../README.md) -> Changelog

This change log references the repository changes and releases, which respect [semantic versioning](https://semver.org).

## DEV

Currently in `dev` branch:

1. `CliRequest` provides `getParamAs*` helper methods for easier parameters' values type casting.

## v1.0.0

The first official release. Mainly focused on Cliff improvements and some additions like:
- more transparent and convenient config builder;
- [generate-autocompletion-scripts.php](tools/cli-toolkit/generate-autocompletion-scripts.php)
  (eases autocompletion init);
- [TerminalFormatter](src/TerminalFormatter.php) (helps format the improved generated help pages);
- [Question](src/Question/Question.php) (helps implement interactive scripts);
- PHPUnit for autotests plus more autotests.
