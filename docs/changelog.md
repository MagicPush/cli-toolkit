# [CliToolkit](../README.md) -> Changelog

This change log references the repository changes and releases, which respect [semantic versioning](https://semver.org).

## v2.1.0

### New features

1. [AutoloadDetector.php](../tools/cli-toolkit/Classes/AutoloadDetector.php) is added for
   [init.php](../tools/cli-toolkit/init.php).

   Previously all built-in scripts ([cli-toolkit](../tools/cli-toolkit)) could not be launched without calling
   `composer install` additionally inside the library directory.
   And from now on your main project's `vendor/autoload.php` path should be detectable.

### Patches

1. PHPUnit version is upgraded from `^10` to `12.1.0`.

   From now on PHPUnit engine itself is not present in the library and should be installed and launched separately.
1. Test classes are auto-loaded only during actual test launches. E.g. if you accidentally use `tests/utils/CliProcess`
   in your production classes under 'dev' environment (when you call `composer install` without `--no-dev` option),
   you will get "Class 'XXX' not found in ..." error.
   Previously there was no error, until you install composer packages with `--no-dev` flag.
1. `HelpGenerator`:
    1. Fixed scripts main description block - stopped counting symbols in space-only lines.
       Previously it caused too much padding for descriptions with too short space-only lines.
    1. Improved subcommand help usage block - when `--help` is called for a subcommand, all manual usage lines
       include the whole path (subcommand values) starting from the topmost config.

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

### Patches

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
