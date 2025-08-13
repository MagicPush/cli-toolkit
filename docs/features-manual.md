# [CliToolkit](../README.md) -> Features Manual

Here are more detailed descriptions for different features you may find in the project...

## Contents

- [Classes or plain scripts](#classes-or-plain-scripts)
    - [Class detection performance](#class-detection-performance)
    - [Class scripts alternative launcher](#class-scripts-alternative-launcher)
- [Parameter types](#parameter-types)
- [Type casting from requests](#type-casting-from-requests)
- [Validators](#validators)
- [Subcommands](#subcommands)
    - [Built-in subcommands](#built-in-subcommands)
- [Environment Config](#environment-config)
    - [How to: Manually via an instance](#how-to-manually-via-an-instance)
    - [How to: Automatically via config files](#how-to-automatically-via-config-files)
    - [Available settings](#available-settings)
- [Interactive scripts](#interactive-scripts)
- [Colorful scripts](#colorful-scripts)

## Classes or plain scripts

Within this library:
* **Plain script** means a script written within a single, self-sufficient file that may be launched as is:
`php script-file.php [parameters...]`.

    * Developing _plain scripts_ might be a better solution for you, only if you do not want (or are not able) to enable
      [completion](../README.md#completion) for some reason - calling a plain script file might be shorter than calling
      a launcher with a class script name.
* **Class-based script** means a file with a class (extended from `ScriptClassAbstract`) that contains a script logic
  and must be executed by a separate launcher (`ScriptClassLauncher`):
  `php your-launcher.php script-name [parameters...]`

    * Generally, _class-based scripts_ are recommended over _plain scripts_ because are more flexible and easier to
      organize and thus maintain, test, debug, reuse, etc.

      Although you may create a single-file script that contain both a class and its execution code,
      _class-based scripts_ written with this library (extended from `ScriptClassAbstract`) contain some built-in stuff
      to decrease your time and efforts needed for creating ready-to-launch scripts
      (that's the main idea lying under the _class-based scripts_).
    * Under the hood `ScriptClassLauncher` instance eventually creates a parent `Config` instance (unless you pass it
      to a launcher constructor explicitly) and fills it with _class-based scripts_' configs as
      **[subcommands](#subcommands)**.

Here is a more detailed comparison between _class-based_ and _plain_ scripts. It covers most
(if not all; apart from obvious and "natural" ability to split a script's logic into class methods)
aspects of developing a console script with this library:

<details>
<summary>(click to unfold)</summary>

* **Executing a script**
    * _Plain_: Just launch a script file: `php script-file.php [parameters...]`
    * _Class-based_: A separate launcher is required: `php your-launcher.php script-name [parameters...]`.
      The launcher must be based on `ScriptClassLauncher` and include `ScriptClassDetector` setup.

      * [Skeleton generator](../README.md#script-classes) provides you with the fast and simple way to generate
        a good set of files to make it work, including a launcher itself, a completion script (with its generator),
        and an class-based script example.
* **Completion alias** (if you enable [completion](../README.md#completion)):
    * _Plain_: Each detected script is linked with its own alias. If you have 500 scripts, then 500 aliases have to be
      loaded into your shell environment.
    * _Class-based_: You need only a single alias for your launcher.
      Or a few aliases, if you want to group your scripts by separate launchers.
* **Listing available scripts**:
    * _Plain_: List available aliases (if you enable completion) or read your project directories for your script file
      names.
    * _Class-based_: Execute the built-in `list` subcommand with your launcher: `php your-launcher.php list`. You may
      even omit `list` (because it is the default subcommand name) and just run the launcher: `php your-launcher.php`
        * See `php your-launcher.php help list` for listing options.
* **Adding a new script to / Removing an obsolete script from the list of available scripts**:
    * _Plain_: Create a script file in a directory and remember the path to the script. Or delete the obsolete script.
        * If you enable completion, then you should re-generate the completion script added to your environment load
          sequence, so it includes an alias for the just created script (or lacks the alias of the just removed script).

          Also, in case a new script is created, you should `source` the updated completion script to load a new
          alias into your environment right away.
    * _Class-based_: Just create a class-based script.
        * Generally, your launcher's detector will detect the new class automatically.
          Otherwise, update the detection rules in the launcher.
        * In case of [caching detected class names](#class-detection-performance), you should re-create the cache file:
          `php your-launcher.php script-launcher:clear-cache`
* **Naming and grouping scripts**:
    * _Plain_: Placing scripts in different directories is your main option.
        * If you enable completion, then you may group your scripts by alias prefixes: generate completion scripts for
          different groups of scripts (defined by generator detection settings) with different alias prefixes.
    * _Class-based_: Script names are compiled from class short names (generated automatically by built-in
      `getScriptInnerName()` method or manually by redefining it) and manually set groups (sections) by (re)defining
      `getNameSections()`.
        * Adding several sections to a script works as adding subgroups: `my:some:cool-script` is `CoolScript` class
          from `some` subgroup within `my` group.
        * `list` subcommand in its default mode reflects groups as leveled headers for easier reading.
        * You may additionally group your scripts by _launcher scripts_: each launcher may have its own unique
          detection rules to access a selected subset of scripts.
</details>

### Class detection performance

`ScriptClassDetector` provides you with these ways (non-exclusive) to set detection rules:

1. `searchDirectory()` allows you to recursively search and parse files inside a specified directory. Optionally,
   performs non-recursive search  (see `isRecursive` parameter).
    * Performance: depends on a directory size and depth - varies from a few milliseconds (up to a few hundred of
      files) to whatever time it takes to analyze tens of thousands files (or more).
    * User-friendly: pretty much - in trivial cases (all scripts are within a single directory or its subdirectories)
      it is enough to specify just a single directory.
    * Use case: in most cases this method covers your needs.
1. `excludeDirectory()` allows you to filter out a directory that definitely should not be parsed. Obviously,
   this method works well only when paired with `searchDirectory()` method in the recursive mode.
    * Use case: the directory specified in `searchDirectory()` contains subdirectories that definitely do not contain
      console scripts. So you can improve the detection performance by filtering out non-relevant subpaths.
3. `scriptClassName()` allows you to point at exact class by specifying its fully qualified name.
    * Performance: "instant" - no files searching and parsing is needed.
    * User-friendly: not much - with detection rules based on this method only you have to explicitly specify each class
      you want to make available for your scripts launcher. Makes your work a bit more tedious.
    * Use cases:
        * Cherry-picking particular scripts.
        * Improving detection performance (instead of pointing at a directory with thousands of files).

Now consider an odd case. You have a large project (gigabytes / tens of thousands of files), but your console scripts
are scattered throughout your whole project for some reason - for instance, each console script is placed close to
a "feature unit". And you do not like the idea to set detection rules by specifying each feature directory with
`searchDirectory()` or each console script class with `scriptClassName()`. In this case you do have one more option:

1. Add a single detection rule with `searchDirectory()` - specify your project root directory or any other path that
   is "high enough" to contain all the scripts you want to detect.

    * Optionally, filter out directories like `vendor`, which should not contain relevant (for you) class-based console
      scripts. This way you will improve the detection performance.
    * From this point your launcher execution may take possibly _dozens of seconds_ - because the detector will need
      time to parse lots of files.
2. Set `cacheFilePath()` for your detector.
    * During the first run of your launcher (with any command, including no command at all) the detector will put all
      detected class names into the file specified on the previous step.
    * All subsequent runs of your launcher will be executed instantly - by loading classes with their fully
      qualified names stored in the specified cache file.
3. Later, if you add (or delete) a script, just launch the special command in your launcher to delete the cache file:
   `php your-launcher.php script-launcher:clear-cache`. Then run you launcher again to re-create the cache file.

    * This subcommand becomes available (and visible in your launcher's list of available commands) as soon as
      `cacheFilePath()` is set and the specified cache file exists.

### Class scripts alternative launcher

Every class script (based on `ScriptClassAbstract`) can be also executed with
[execute-class.php](../tools/cli-toolkit/execute-class.php) script. Just specify a class name as its first parameter.

See the script description located at the top of that file.

## Parameter types

**Arguments** (positional parameters) are parameters for which you specify only values in a strict order.

**Options** are parameters specified by names (start with `--`). Some options are provided with short
one-letter aliases, for instance `-v` for `--verbose`.
- _Options_ may be specified in any position - before, after or even between _arguments_:
  `$ php my-cool-script.php --chunk-size=100 data.csv --chunk-pause=500`
    - The exception is subcommands: options must be specified before a subcommand name (before "moving to a lower
      level parameters").
- _Options_ configured with short names may be specified as one word (`-xvalue`) or in a separate manner ('-x value'):
  `$ php my-cool-script.php -s 100 data.csv -p500`, where `100` is `-s` _option_ value,
  `data.csv` is an _argument_ and `500` is `-p` _option_ value.

**Flags** — _options_ that require no value and are specified by names only:
`$ php my-cool-script.php --verbose`, where `--verbose` is a _flag_ name.
- _Flags_ configured with short names may be specified as one word (`-abc`)
  or separately (`-a -b -c`).

### Double dash / hyphen

The special "double-hyphen" argument (`--`) is supported: anything specified after `--` is considered as an _argument_
value only. This is suitable if you want to specify for instance an _argument_ value which contains leading hyphens.
- https://www.gnu.org/software/libc/manual/html_node/Argument-Syntax.html
- https://unix.stackexchange.com/questions/11376/what-does-double-dash-mean

### Why positional parameters are called "arguments"

There are different opinions on how to call a positional parameter. The main reason here to call it as "argument"
is just that a very popular CLI framework `symfony/console` calls it the same way.
So if you used to write CLI PHP scripts via `symfony`, you won't have any trouble with this term.

## Type casting from requests

After command-line parameters are processed, `Parametizer::run()` returns an instance of `CliRequest`. Then you can
read the parsed parameters' values from that request object via `getParam()` method.

Initially parsed values are rendered as mixed
(usually as strings and flags as booleans, but custom validators may change value types).
And usually you would like to cast those values to more appropriate data types. You can do it by a standard way like
`(int) $request->getParam('cycles-count')` or via the special helper methods:

```php
$request = Parametizer::newConfig()
    ->newFlag('--verbose')
    ->newOption('cycles-count')
    ->newOption('temperature')
    ->newArrayOption('list-of-ids')
    ->newArrayOption('list-of-coords')

$isVerbose   = $request->getParamAsBool('verbose');      // Flag values are always converted to bool automatically,
                                                         // but this way is more IDE-friendly.
$cyclesCount = $request->getParamAsInt('cycles-count');  // Instead of `(int) $request->getParam('cycles-count')`.
$temperature = $request->getParamAsFloat('temperature'); // Instead of `(float) $request->getParam('temperature')`.

// Each element of an array is casted to ...
$ids    = $request->getParamAsIntList('list-of-ids');      // ... an integer value.
$coords = $request->getParamAsFloatList('list-of-coords'); // ... a float value.
```

In addition, all `getParamAs*()` helpers execute a basic validation ensuring that you do not try to cast single values
into arrays and vice versa. If you want some custom value casting or filtering, you can always do it
via `validatorCallback()` (see [Validators](#validators) below).

## Validators

Ensure your script can be called with valid values only.

Configure possible values list:

```php
$request = Parametizer::newConfig()
    ->newArgument('chunk-size')
    ->allowedValues([10, 50, 100, 200, 500])

    ->run();
```

```
$ php my-cool-script.php 1000
Incorrect value '1000' for argument <chunk-size>
```

...or provide a pattern:

```php
$request = Parametizer::newConfig()
    ->newArgument('chunk-size')
    ->validatorPattern('/^[0-9]+$/')

    ->run();
```

```
$ php my-cool-script.php 200s
Incorrect value '200s' for argument <chunk-size>
```

...or even specify a callback:

```php
$request = Parametizer::newConfig()
    ->newArgument('chunk-size')
    ->validatorCallback(function (&$value) { // Values can be rewritten in callbacks, if you desire.
        return $value > 0 && $value <= 500;
    })

    ->run();
```

```
$ php my-cool-script.php 510
Incorrect value '510' for argument <chunk-size>
```

## Subcommands

In some cases you might want to have such a script, where one part of parameters is common for every launch and another
part of parameters differs quite significantly depending on a script's branch (subcommand). Technically it turns a
script parameters config into a tree of configs with the base config (like a 'trunk') and 'branched' configs, where
each 'branch' has its own config with parameters available only within that particular branch.

Common examples of such constructs:
- `git push --verify` and `git tag --verify`: a parameter with the same name `--verify` acts differently depending on
  a subcommand selected (`push` or `tag`);
- `composer install --download-only` and `composer update --root-reqs`: `--download-only` flag is not available for
  `update` subcommand so as `--root-reqs` flag - for `install` subcommand.
  
Consider such a script:
```php
$request = Parametizer::newConfig()
    /*
     * The `--help` flag is added automatically for each config - the trunk and all branches.
     * Thus you are able to see different help pages depending on the position of the flag in a command line.
     */

    /*
     * This argument exists on the base 'level' and should be passed before a subcommand name,
     * but its value is available everywhere in a script's configs tree.
     */
    ->newArgument('file-path')
    ->description('Path to a file for processing.')
    
    /*
     * The subcommand switch name works like an argument with a few exceptions:
     *  - the method call may be omitted - `newSubcommand()` call will invoke adding a switch automatically;
     *      * call this method directly only if you want a custom name, description
     *        or any other customization for the switch;
     *  - must be the last argument in the current config
     *  (thus also excluding a possibility to define more subcommand switches in the same config).
     */
    ->newSubcommandSwitch('operation')
    /*
     * Here you define as many subcommands (nested configs or 'branches') as you wish.
     * The first parameter here is a subcommand (or branch) name.
     * When you specify this string as a subcommand switch value, this branch config takes effect.
     */
    ->newSubcommand(
        'read',
        Parametizer::newConfig()
        // If you do not need any other parameters, you can leave an 'empty' config here.
    )
    ->newSubcommand(
        'write',
        Parametizer::newConfig()
            ->newFlag('--truncate')
            ->description('
                Truncate the whole file before writing into it.
                By default, the string is appended to the end of a file.
            ')
            
            ->newArgument('substring'),
            
            /*
             * If you dare, you may create an even more complex tree of subcommands,
             * as it is possible to add a subcommand switch to every config: 
             */
            //->newSubcommandSwitch('sub-operation')
            //->newSubcommand(
            //    'super'
            //     Parametizer::newConfig()
            //        -> ...
            //        ->newSubcommandSwitch('even-deeper')
            //        ->newSubcommand(...)
            //        ...
            // )
            // ->newSubcommand(
            //    'mega'
            //     Parametizer::newConfig()
            //        -> ...
            // )
    )

    ->run();

$filePath  = $request->getParamAsString('file-path');
$operation = $request->getParamAsString('operation');
// Here you get a sub-request for a corresponding branch config.
$operationRequest = $request->getSubcommandRequest($operation);
switch ($operation) {
    case 'read':
        // ...
        break;
        
    case 'write':
        $shouldTruncate = $operationRequest->getParamAsBool('truncate');
        // ...
        break;
}
```

With such a script:
1. You may request a help page for the common part (`script.php --help`)
   or for one of subcommands (`script.php write --help`).
1. You have to specify options and arguments for a particular 'level' (config) before or after a subcommand name
   depending on what config you want to affect with parameters: parent config parameters must be specified before
   a subcommand name, subcommand config parameters - after a subcommand name.

   For instance, you can not specify `--truncate` flag before specifying `write` (the subcommand
   that supports `--truncate` flag): `script.php --truncate write` will render an error about an unknown option,
   but `script.php write --truncate` will be executed correctly.

   Or you can not request the main command help when specifying `--help` after `read`,
   because this way you invoke a help page generation for the `read` subcommand.
   
### Built-in subcommands

Each config with at least one subcommand (or a subcommand switch registered explicitly) will automatically register
the _built-in subcommands_:

1. `help` subcommand works just as alternative for `--help` flag: the command `php script.php help cool-stuff` is
   identical to the command `php script.php cool-stuff --help`: a help page for `cool-stuff` subcommand will be shown.
   You can use whatever form you find more convenient.
2. `list` subcommand outputs a list of available subcommands.
    * Read more about `list` parameters by accessing its help page as any other config help:
      `php script.php help list` or `php script.php list --help`
    * It is the default value for every subcommand switch: the command `php script.php` will run identically to
      the command `php script.php list` (assuming that `script.php` config contains a subcommand switch).

      It should be noted however, that passing parameters to `list` subcommand will work only if `list` subcommand is
      specified explicitly: `php script.php list --slim`. Otherwise parameters will be treated as a parent config's
      parameters: `php script.php --slim` may render `Unknown option '--slim'` error message (unless your parent
      config's definition contains its own `--slim` option).
    * A common issue with commands listing is description not fitting the terminal screen width and appearing on
      subsequent lines, which eventually makes the output reading less convenient. To counter this issue, there is
      a mechanism that tries to automatically trim a subcommand's description gracefully (see
      [helpGeneratorShortDescription*](#helpgeneratorshortdescriptioncharsminbeforefullstop)
      `EnvironmentConfig` options).

      Additionally, you may manually set an exact short description in a subcommand config builder with
      `shortDescription()`. If a value is present, then that exact value will be always shown as is (without trimming)
      in `list` output.

      As a bonus, if your subcommand full description is short enough, you may set it with
      `shortDescription()` and omit `description()` completely - the short description will be shown correctly both
      on subcommand listing and subcommand help page.

## Environment Config

You may want to alter some general behavior for all or a part of your scripts. Here comes `EnvironmentConfig`.

### How to: Manually via an instance

Parametizer script config constructor lets you pass your custom `EnvironmentConfig` instance:

```php
$envConfig = new EnvironmentConfig();

$envConfig->optionHelpShortName = 'h';

$request = Parametizer::newConfig($envConfig)
    // ...
```

If your script supports subcommands, ensure providing all subcommands with environment configs,
unless you want the default behavior for all or some of subcommands:
```php
$request = Parametizer::newConfig($envConfig)
    ->newSubcommand(
        'command-1',
        Parametizer::newConfig($envConfig)
        // ...
    )
    ->newSubcommand(
        'command-2',
        Parametizer::newConfig($specialSubcommandEnvConfig)
        // ...
    )
    // ...
```

Setting an instance might be nice for you if you want to alter the behavior for a single or a few scripts.

However if you want to affect a large amount of scripts or even all of those, then read below...

### How to: Automatically via config files

1. Generate a config file with the command:
   ```shell
   php ../tools/cli-toolkit/run.php cli-toolkit:generate:environment-config-file --help
   ```
1. Edit the generated file as you please.
1. Choose which scripts should be affected:
    * If you want to affect all your scripts, just place this file in your project root directory
      or your console scripts root directory.
    * If you want to affect only a part of your scripts, move those scripts to a separate subdirectory and place
the config file there in the same directory as those scripts.

If `Parametizer::newConfig()` is called without a particular `EnvironmentConfig` instance passed in it
(or `null` is specified explicitly), then an `EnvironmentConfig` instance is generated automatically
from config files it detects.

#### Detection

The detection works this way:
1. Detect the bottommost lookup directory: start looking for a config file in the same directory where
   the launched script file is located.
    * If a launched script's backtrace contains calls from `ScriptClassAbstract`, then such the backtrace entry
       closest to the launched script is chosen. Thus the detected subcommand class location is prioritized over
       the launched script location (see _Example 2_ below).
1. If a config file is not found or contains only a part of settings, move 1 directory above the current and repeat.
1. Continue the search until all settings have been read from found files or the _topmost directory_ is reached.

The _topmost directory_ is calculated by searching the project root directory - the topmost directory with
`vendor` subdirectory in the whole filesystem. If there is no `vendor` directory found along the way,
then the _topmost directory_ is the filesystem root directory.

#### Hierarchy

A config file may contain only a part of settings or even a single one. In this case the environment config autoloader
will read and set only the specified settings and will not affect other settings.

A config file located in your particular scripts subdirectory is prioritized over other config files found in
directories above. You may place a general config file in your project root directory and then a specialized config
file in a particular subdirectory.

The config files autoload hierarchy works this way:
1. Continue looking for files until all settings have been read from found files or the _topmost directory_ is reached.
1. For each setting specified in a detected config file set values only for the settings not filled by previously
   detected config files. In other words, affect only settings that contain default values.

**Example 1 - plain scripts:**

```
project_root/
    parametizer.env.json
    scripts/
        script1.php
        script2.php
        special-scripts/
            parametizer.env.json
            special1.php
            special2.php
    vendor/
```

When launching `project_root/scripts/script?.php` only the `project_root/parametizer.env.json` is considered.

However, when launching `project_root/scripts/special-scripts/special?.php` the autoloader firstly fills
an `EnvironmentConfig` instance with the contents of `project_root/scripts/special-scripts/parametizer.env.json` and
only after it (if there are settings with default values left) fills the rest with the contents of
`project_root/parametizer.env.json`. The settings not mentioned in both config files keep their respective default
values.

**Example 2 - scripts with subcommand classes:**

```
somewhere/
    Scripts/
        CoolScript.php 
        parametizer.env.json
    launchers/
        launcher.php
        parametizer.env.json
    parametizer.env.json
```
where `CoolScript.php` is a subclass (directly or through "relative" classes in between) of `ScriptClassAbstract`.

When launching `somewhere/launchers/launcher.php` with some other subcommand (or without a subcommand - `... --help`,
for instance), the `EnvironmentConfig` autoloader will detect and load `somewhere/launchers/parametizer.env.json`.
Then, if an instance is not filled completely, `somewhere/parametizer.env.json` in the parent (to `somewhere/launchers`)
directory is considered next.

However, when launching `somewhere/launchers/launcher.php cool-script`, the `EnvironmentConfig` autoloader will detect
`somewhere/Scripts/parametizer.env.json` instead - the config file located in the same directory as
`cool-script` subcommand class directory. Then if an instance is not filled completely,
again `somewhere/parametizer.env.json` in the parent (now to `somewhere/Scripts`) directory is considered next.
In this example `somewhere/launchers/parametizer.env.json` config file is read only
by `somewhere/launchers/launcher.php` main config.

### Available settings

#### listPaddingLeftMain

* Affects the padding from the left border of a terminal for `list` [built-in subcommand](#built-in-subcommands) output.
  The padding precedes each line (headers and command names).

    * The setting is ignored (treated as `0`) if `--slim` output mode is enabled.
* Controls the padding size as the number of space characters.
* Possible values: non-negative `int`.
    * Values below `0` are silently treated as `0`.

#### listPaddingLeftCommand

* Affects the padding to the left of a command or subsequent header (in addition to
  [listPaddingLeftMain](#listpaddingleftmain)) for `list` [built-in subcommand](#built-in-subcommands) output.

    * The setting is ignored (treated as `0`) if `--slim` output mode is enabled.
* Controls the padding size as the number of space characters.
* Possible values: non-negative `int`.
    * Values below `0` are silently treated as `0`.
    
Consider a command name `some:cool:command` with [listPaddingLeftMain](#listpaddingleftmain) = 1 and this setting = 4.
The command contains 2 sections, so after the main header (the first section) 2 more lines will be shown - a subsection
and a full command name:

```
# This line is added here as a reference - it does not have any left padding.
 some:
     some:cool:
         some:cool:command
```

The first (main) header is padded with [listPaddingLeftMain](#listpaddingleftmain), however subsequent header and
the command itself are additionally padded with this setting, `listPaddingLeftCommand`.

#### listPaddingLeftCommandDescription

* Affects the minimum left padding for all commands descriptions outputted with `list`
  [built-in subcommand](#built-in-subcommands).
  
  The maximum padding depends on the longest command name "column", which consists of
  [listPaddingLeftMain](#listpaddingleftmain), [listPaddingLeftCommand](#listpaddingleftcommand) for each section, and
  the command full name (including name sections).
* Controls the padding size as the number of space characters.
* Possible values: non-negative `int`.
    * Values below `0` are silently treated as `0`.

#### helpGeneratorPaddingLeftMain

* Affects the padding from the left border of a terminal. The padding precedes almost all lines from generated `help`
  pages (except headers) - script's description, usage template and examples, parameters' names.
* Controls the padding size as the number of space characters.
* Possible values: non-negative `int`.
    * Values below `0` are silently treated as `0`.

#### helpGeneratorPaddingLeftParameterDescription

* Affects the minimum left padding for parameters' descriptions outputted on a generated `help` page.

  The maximum padding depends on the longest parameter name "column", which consists of
  [helpGeneratorPaddingLeftMain](#helpgeneratorpaddingleftmain) and a parameter formatted name.
* Controls the padding size as the number of space characters.
* Possible values: non-negative `int`.
    * Values below `0` are silently treated as `0`.

#### helpGeneratorShortDescriptionCharsMinBeforeFullStop

* Affects scripts descriptions' graceful trimming (usually seen while listing available subcommands).
* Controls a min length of a description substring cut around a full sentence (a substring ending with `. `).
* Possible values: any (reasonable) `int`.
    * Values bigger than [helpGeneratorShortDescriptionCharsMax](#helpgeneratorshortdescriptioncharsmax) are
    ignored naturally.

When creating a short version of a description, firstly a full sentence is tried being found.

Consider a full description:
```
Too short string. Another shorty. The rest adds much more characters, what makes the whole line too long.
```

With the setting value `18` or lower, the short description will be `Too short string.`. If it is too short for you,
you may increase the value up to `34` and then you get `Too short string. Another shorty.`.

But if you specify a bigger value, the setting is _naturally_ ignored - no full sentence is found at the specified
cursor position. So [helpGeneratorShortDescriptionCharsMax](#helpgeneratorshortdescriptioncharsmax) is considered
the next.

#### helpGeneratorShortDescriptionCharsMax

* Affects scripts descriptions' graceful trimming (usually seen while listing available subcommands).
* Controls a max length of a description substring.
  But firstly tries cutting a description gracefully (by a space character).
* Possible values: any (reasonable) `int`.

Consider a full description:
```
Too short string. Another shorty. The rest adds much more characters, what makes the whole line too long.
```

If the setting value is `60`
and [helpGeneratorShortDescriptionCharsMinBeforeFullStop](#helpgeneratorshortdescriptioncharsminbeforefullstop) is too
big (`35` or bigger), the short description could be `Too short string. Another shorty. The rest adds much more ch`
(exactly 60 chars), but if a space character is found before the max length cursor, the last part (` ch`, a piece of
an incomplete word) is cut: `Too short string. Another shorty. The rest adds much more`

#### helpGeneratorUsageNonRequiredOptionsMax

* Affects only _usage templates_ on scripts' generated `help` pages.
* Controls if each non-required _option_ (including _flags_) is shown or `[options]` substring is outputted instead.
* Possible values: positive `int`.
    * Values less than `1` guarantee no long option names are shown.

Consider a config below:

```php
$configBuilder
    ->newOption('--chunk-size')
    ->newOption('--chunk-pause')

    ->newFlag('--verbose', '-v')
    ->newFlag('--interactive', '-i')

    ->newOption('--database')
    ->required()

    ->newOption('--table')
    ->required();
```

This config contains _4_ non-required options - 2 standard options `--chunk-size` and `--chunk-pause`,
2 flags `--verbose` and `--intercative` with their respective short names `-v` and `-i`.
Depending on the environment config setting the usage template output will be different:

1. If the setting value is `4` or higher:
   `script.php [-vi] [--chunk-size=…] [--chunk-pause=…] [--verbose] [--interactive] --database=… --table=…`
2. If the setting value is `3` or lower: `script.php [-vi] [options] --database=… --table=…`

   Note that all 4 options long names were replaced with `[options]` substring, but flag short names (`[-vi]`)
   and all required options (`--database` ,`--table`) are still outputted as is.

The setting controls how lengthy might be usage templates for your scripts. Set it to `0` to force it always
show `[options]`. Set it to a very high value to ensure all non-required option names are always shown in a template.
Or pick some optimal value to see full templates for not very option-heavy scripts.

#### optionHelpShortName

* Controls if the `--help` option has a short name or does not.
* Possible values:
    * a latin character (like for any other option short name)
    * `null` (no short name)

`--help` option is automatically added for all scripts and subcommands. Usually you may want to add a short name `-h`
to request help pages easier, but then you will not be able to use `-h` as a short name for your other parameters
like `--host` because of the duplication check.

With this setting you may choose which scripts get a short name for `--help` (and what) and which do not.

## Interactive scripts

`Question` class provides you with a few ways of interactivity for your scripts (not necessarily `Parametizer`-powered).

Consider `bet.php` script as an example:

```php
<?php

// ... some init

// Execution is stopped if 'N' (default) or 'n' answer is given:
Question::confirmOrDie('Confirm to proceed', 'ABORTED' . PHP_EOL);

// Just stores a boolean representation of 'Y'/'y'/'N'/'n' answer.
$isVerbose = Question::confirm('Do you want it to be verbose?');

// Requests an input line with no validation.
$name = Question::askAway('Your name');

// You can setup "questions" that require one of particular possible answers (case sensitive or not):
$horse = Question::create('Pick your lucky horse')
    ->possibleAnswers(['Agape', 'Athena', 'Cora', 'Daphne', 'Iris', 'Theo'], isCaseSensitive: false)
    ->substringAfterQuestion(<<<TEXT

                    .''
          ._.-.___.' (`\
         //(        ( `'
        '/ )\ ).__. ) 
        ' <' `\ ._/'\
           `   \     \
        > 
        TEXT)
    ->ask();

// Or you can validate answers with a regexp pattern:
$bet = Question::create('Place your bet')
    ->defaultAnswer('100')
    ->answerValidatorPattern('/^[0-9]+$/')
    ->ask();

echo 'Your participation is recorded';
if ($isVerbose) {
    echo <<<TEXT
        :
        {$name} bet {$bet} bitcoins on {$horse}.

        TEXT;
} else {
    echo '.' . PHP_EOL;
}

```

Possible input and output:

```
$ php bet.php
Confirm to proceed Y / N (N): 
ABORTED

$ php bet.php
Confirm to proceed Y / N (N): Y
Do you want it to be verbose? Y / N (N): y
Your name: Mario
Pick your lucky horse Agape / Athena / Cora / Daphne / Iris / Theo
            .''
  ._.-.___.' (`\
 //(        ( `'
'/ )\ ).__. ) 
' <' `\ ._/'\
   `   \     \
> dafne
Invalid answer. Possible answers: Agape, Athena, Cora, Daphne, Iris, Theo

Pick your lucky horse Agape / Athena / Cora / Daphne / Iris / Theo
            .''
  ._.-.___.' (`\
 //(        ( `'
'/ )\ ).__. ) 
' <' `\ ._/'\
   `   \     \
> daphne
Place your bet (100): -1
Invalid answer.

Place your bet (100): 10
Your participation is recorded:
Mario bet 10 bitcoins on Daphne.
```

## Colorful scripts

`TerminalFormatter` class adds escape sequences to strings, which may affect font color, font style, and
background color. The key method here is `apply()` - it surrounds an input string with escape sequences, where
the first enables formatting and the second disables it. See class constants and comments around those for details. 

* Nested formatting is supported: `$formatter->error('Exception "' . $formatter->note('Weee!') . '" message')`
  will result in a string with sequences starting and ending in correct places, so inner substring `Weee!` is formatted
  as expected, and the latter outer part keeps its original "error" formatting. 
* Create instances of a formatter with `createForStdOut()` or `createForStdErr()`, so escape sequences are automatically
  skipped (not added), if actual output happens in a non-expected stream. For instance, with `createForStdOut()`
  the output will be formatted while shown in a terminal, unless the output is redirected to a file.
* It is recommended to extend your formatter class from `TerminalFormatter` and add human readable shortcuts, so you
  can format your error messages with `$yourFormatter->error($message)` instead of
  `$terminalFormatter->apply($message, [TerminalFormatter::STYLE_BOLD, TerminalFormatter::FONT_RED])`.
