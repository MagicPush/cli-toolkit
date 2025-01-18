# [CliToolkit](../README.md) -> Changelog

This change log references the repository changes and releases, which respect [semantic versioning](https://semver.org).

## v2.0.0

### Backward incompatibilities:

1. PHP minimal required version is **8.3**.
    1. Set explicitly types for all classes constants.
1. `Config::createHelpOption()` is deleted because not needed anymore (see the point below).
1. `Parametizer::setExceptionHandlerForParsing()` requires a `CliRequestProcessor` object,
   `HelpGenerator::getUsageForParseErrorException()` requires a `Config` object as the second parameter,
   `CliRequestProcessor::__construct()` is added with a required `Config` object parameter,
   `CliRequestProcessor::load()` signature is changed - `Config` object parameter is removed.
   This way it's possible to get an innermost branch config for `ParseErrorException` instances.
1. `$isForStdErr` parameter is removed from `HelpGenerator::getUsageForParseErrorException()`, the method always use
   a formatter for STDERR stream.
1. `Parametizer::setExceptionHandlerForParsing()` renders both an error message and a help block in STDERR (previously
   a help block was rendered in STDOUT).
1. `Option::getOptionCleanFullName()` is deleted because of no usage.
   
1. Renaming:
    1. `CliRequest::getCommandRequest()` -> `getSubcommandRequest()`
    1. `CliRequestProcessor::getAllowedArguments()` -> `getInnermostBranchAllowedArguments()`
    1. `CliRequestProcessor::append()` -> `appendToInnermostBranch()`
1. `HelpGenerator::getUsageTemplate()` is made _protected_ (from _public_) and non-static. Then it's able to use
an instance `$formatter` property (instead of creating a separate formatter instance).

#### ... only if `DEV` is merged into master before this branch:

1. `HelpGenerator::getShortDescription()` requires 2 more parameters - min and max amount of chars.
1. `HelpGenerator::SHORT_DESCRIPTION_MIN_CHARS` and `SHORT_DESCRIPTION_MAX_CHARS` constants are removed.

### New features

1. Added [Environment Config](features-manual.md#environment-config) that allows setting "environment"-related
   options like a short name for built-in `--help` option and short description boundaries.
   Other changes provoked by this addition:
    1. `Config::__construct()`, `ConfigBuilder::__construct()` and `Parametizer::newConfig()` support
       a new optional parameter `$envConfig` to pass `EnvironmentConfig` instance.
    1. Added `Config::$envConfig` protected property to keep `EnvironmentConfig` instance
       and the related `getEnvConfig()` public method.
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
- [generate-autocompletion-scripts.php](../tools/cli-toolkit/generate-autocompletion-scripts.php)
  (eases autocompletion init);
- [TerminalFormatter](../src/TerminalFormatter.php) (helps format the improved generated help pages);
- [Question](../src/Question/Question.php) (helps implement interactive scripts);
- PHPUnit for autotests plus more autotests.
