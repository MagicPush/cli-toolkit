# [CliToolkit](../README.md) -> Changelog

This change log references the repository changes and releases, which respect [semantic versioning](https://semver.org).

## v3.0.0

### Backward incompatibilities:

1. Fixed a possible bug when a parent config parameter value is replaced with a subcommand parameters' values
   in a request multi-dimensional array. Example: when adding `list` parameter to a main config,
   it's value was then replaced with `list` _subcommand_ sub-request values.
   
   The fix became possible with adding a special prefix to a request array key connected to
   a subcommand request values sub-array. Such a prefix can not be added to a parameter or subcommand name without
   a configuration error, which makes such request key naming safe.
   
   If previously you could access `magic` subcommand request parameters from the main request
   by `$sub-request = $request->getParams()['magic']`, from now on you have to do it this way:
   `$sub-request = $request->getParams()[CliRequest::SUBCOMMAND_PREFIX . 'magic']`.
   
   ... However in the majority of cases you should be OK with the built-in handy method
   `$request->getSubcommandRequest()`,
   which does not require specifying a chosen subcommand name - it detects that name automatically.
    * Added `CliRequest::SUBCOMMAND_PREFIX` for subcommand request key names.
1. [cli-toolkit](../tools/cli-toolkit) plain scripts are removed to be replaced with
   [ScriptAbstract.php](../src/Parametizer/Script/ScriptAbstract.php)-based scripts
   and a [launcher.php](../tools/cli-toolkit/launcher.php).
1. `HelpGenerator::getSubcommandsBlock()` is removed - replaced with `list` built-in subcommand.
1. `CliRequest::getSubcommandRequest()`: removed `$subcommandName` parameter, changed return type hint
   from `string` to `?string`:
   now the method always returns the called subcommand's request object or `null` if there was no subcommand call.
1. `CliRequestProcessor::$config` became readonly - you can not edit it after passing to `__construct()`.
1. [HelpGenerator.php](../src/Parametizer/Config/HelpGenerator.php):
    1. Removed `getSubcommandsBlock()` (so as "COMMANDS" block output from `getFullHelp()`)
       to replace it with `list` built-in subcommand functionality.
    1. Removed `getBaseScriptName()` obsolete method.

### New features

1. Removed "minimum 2 subcommands" constraint from `Config::commitSubcommandSwitch()`.
1. [ScriptAbstract.php](../src/Parametizer/Script/ScriptAbstract.php) as a basement for
   class-based Parametizer-powered scripts.
1. Subcommand names (`Config::newSubcommand()`) now support the colon (`:`) symbol.
   Main purpose - a separator for script classes sections.
1. [ScriptDetector.php](../src/Parametizer/Script/ScriptDetector.php) for different script types auto-detection.
   For now only [ScriptAbstract.php](../src/Parametizer/Script/ScriptAbstract.php)-based scripts are supported.
1. Built-in subcommands: each script with a subcommand switch automatically provides you with
   `help` ([HelpScript.php](../src/Parametizer/Script/BuiltInSubcommand/HelpScript.php))
   and `list` ([ListScript.php](../src/Parametizer/Script/BuiltInSubcommand/ListScript.php)) built-in subcommands.
    1. Every subcommand switch goes with `list` as its default value.
1. [VariableBuilderAbstract.php](../src/Parametizer/Config/Builder/VariableBuilderAbstract.php):
    1. Added an optional `$areHiddenForHelp` parameter for `allowedValues()` method, defaults to `false`.
    1. Added a protected method `setAllowedValues()` for common internal operations with the allowed values list.
1. [ParameterAbstract.php](../src/Parametizer/Config/Parameter/ParameterAbstract.php):
   added a protected property `$areAllowedValuesHiddenFromHelp`,
   a related getter method `areAllowedValuesHiddenFromHelp()`,
   and a related optional parameter `$areHiddenFromHelp` for `allowedValues()` method.
1. `HelpGenerator::makeParamDescription()`:
    * Utilizes `$areAllowedValuesHiddenFromHelp` parameter property and does not show the list of values
      if the flag is set to `true`.
    * For subcommands: replaces "Allowed values" actual list with a hint about detected subcommands count
      and a subcommand name to show all available subcommands.
1. [CliRequest.php](../src/Parametizer/CliRequest/CliRequest.php):
    1. `__construct()` changes:
        1. `$config` parameter is made _public_ (from _protected_).
        1. Added `$parent` parameter to access parent request (from a subcommand request).
    1. Added `getSubcommandRequestName()` method.
    1. Added `executeBuiltInSubcommandIfRequested()` method for built-in subcommands automatic execution;
       the method is utilized by `Parametizer::run()`.
1. Added `CliRequestProcessor::parseSubcommandParameters()` protected method
   to ease processing of the default subcommand value.
1. Added `CliRequestProcessor::$isForCompletion` readonly flag (settable in `__construct()`). The flag is used
   to stabilize completion output due to the default subcommand switch value.
1. [Config.php](../src/Parametizer/Config/Config.php):
    1. Added `PARAMETER_NAME_LIST` public constant to keep the listing built-in subcommand name.
    1. Added `$builtInSubcommandClassBySubcommandName` protected property and the related methods:
        * public `getSubcommandSwitchName()` - to get a subcommand switch parameter name if present in a config;
        * public `getBuiltInSubcommandClass()` - to get a subcommand fully qualified class name by a subcommand name;
        * public `getBuiltInSubcommands()` - to get the list of built-in subcommand configs by their names;
        * protected `addBuiltInSubcommands()` - an internal method to add built-in subcommands automatically to every
          script that contains a subcommand switch parameter.
    1. Added `getArgumentsByNames()` that has argument names as keys, while `getArguments()` still renders numeric keys.
1. [CliToolkitScriptAbstract.php](../tools/cli-toolkit/Scripts/CliToolkitScriptAbstract.php) as a basement for all
   [tools/cli-toolkit](../tools/cli-toolkit) scripts.
1. [GenerateMassTestScripts.php](../tools/cli-toolkit/Scripts/Internal/GenerateMassTestScripts.php) as a tool
   to test the performance and other "law of large numbers" cases, when a launcher includes lots of class scripts.
1. Formatters:
    1. Added `HelpFormatter::invert()`.
    1. Added `ScriptFormatter::note()`.
1. Added `$dieMessage` parameter to `Question::confirmOrDie()` method - outputs a message (if provided)
   before interrupting script's execution.

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
1. [HelpGenerator.php](../src/Parametizer/Config/HelpGenerator.php):
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
