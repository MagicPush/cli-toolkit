# [CliToolkit](../README.md) -> Features Manual

Here are more detailed descriptions for different features you may find in the project...

## Contents

- [Parameter types](#parameter-types)
- [Type casting from requests](#type-casting-from-requests)
- [Validators](#validators)
- [Subcommands](#subcommands)
- [Environment Config](#environment-config)
    - [How to: Manually via an instance](#how-to-manually-via-an-instance)
    - [How to: Automatically via config files](#how-to-automatically-via-config-files)
    - [Available settings](#available-settings)

## Parameter types

**Arguments** (positional parameters) are parameters for which you specify only values in a strict order.

**Options** are parameters specified by names (start with `--`). Some options are provided with short
one-letter aliases, for instance `-v` for `--verbose`.
- _Options_ may be specified in any position - before, after or even between _arguments_:
  `$ php my-cool-script.php --chunk-size=100 data.csv --chunk-pause=500`
    - The exception is subcommands: options must be specified before a subcommand value (before "moving to a lower
      level parameters").
- _Options_ configured with short names may be specified as one word (`-xvalue`) or in a separate manner ('-x value'):
  `$ php my-cool-script.php -s 100 data.csv -p500`, where `100` is `-s` _option_ value,
  `data.csv` is an _argument_ and `500` is `-p` _option_ value.

**Flags** — options that require no value and are specified by names only:
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
     *  - can not be optional;
     *  - must be the last argument in the current config
     *  (thus also excluding a possibility to define more subcommand switches in the same config).
     */
    ->newSubcommandSwitch('operation')
    /*
     * Here you define as many subcommands (nested configs or 'branches') as you wish.
     * The first parameter here is the substring a script user should specify as a subcommand switch value,
     * so the corresponding branch takes effect.
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
             * as it is possible to add a subcommand switch to each and every config: 
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
1. You have to specify options and arguments for a specific 'level' (subcommand, config) before you specify
   a subcommand name.

   For instance, you can not specify `--truncate` flag before specifying `write` (the subcommand
   that supports `--truncate` flag): `script.php --truncate write` will render an error about an unknown option,
   but `script.php write --truncate` will be executed correctly.

   Or you can not request the main command help when specifying `--help` after `read`,
   because this way you invoke a help page generation for the `read` subcommand.

## Environment Config

You may want to alter some general behavior for all or a part of your scripts.
Here comes [EnvironmentConfig.php](../src/Parametizer/EnvironmentConfig.php).

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
    ->newSubcommandSwitch('operation')
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

1. Generate a config file via Parametizer-powered
   [GenerateEnvConfig.php](../tools/cli-toolkit/Scripts/GenerateEnvConfig.php),
   ```sh
   php ../tools/cli-toolkit/launcher.php cli-toolkit:generate-env-config --help
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
    * If a launched script's backtrace contains calls from
       [ScriptAbstract.php](../src/Parametizer/Script/ScriptAbstract.php), then such the backtrace entry closest
       to the launched script is chosen. Thus the detected subcommand class location is prioritized over
       the launched script location (see _Example 2_ below).

       **The caveat**: `EnvironmentConfig` autoloader will detect config files near only those subcommand classes
      with `getConfiguration()` method defined explicitly, even if the method just calls it's parent.
      The detection is based on `debug_backtrace()` output, so a class location is detected only
      if exactly that class `getConfiguration()` version is called.
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
where `CoolScript.php` is a subclass (directly or through "relative" classes in between) of
[ScriptAbstract.php](../src/Parametizer/Script/ScriptAbstract.php).

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

#### optionHelpShortName

* Controls if the `--help` option has a short name or does not.
* Possible values:
    * a latin character (like for any other option short name)
    * `null` (no short name)

`--help` option is automatically added for all scripts and subcommands. Usually you may want to add a short name `-h`
to request help pages easier, but then you will not be able to use `-h` as a short name for your other parameters
like `--host` because of the duplication check.

With this setting you may choose which scripts get a short name for `--help` (and what) and which do not.

#### helpGeneratorShortDescriptionCharsMinBeforeFullStop

* Affects scripts descriptions' short versions (usually seen on scripts' help pages for available subcommands).
* Controls a min length of a description substring cut around a full sentence (a substring ending with `. `).
* Possible values: any (reasonable) `int`
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

* Affects scripts descriptions' short versions (usually seen on scripts' help pages for available subcommands).
* Controls a max length of a description substring.
  But firstly tries cutting a description gracefully (by a space character).
* Possible values: any (reasonable) `int`

Consider a full description:
```
Too short string. Another shorty. The rest adds much more characters, what makes the whole line too long.
```

If the setting value is `60`
and [helpGeneratorShortDescriptionCharsMinBeforeFullStop](#helpgeneratorshortdescriptioncharsminbeforefullstop) is too
big (`35` or bigger), the short description could be `Too short string. Another shorty. The rest adds much more ch`
(exactly 60 chars), but if a space character is found before the max length cursor, the last part (` ch`, a piece of
an incomplete word) is cut: `Too short string. Another shorty. The rest adds much more`.
