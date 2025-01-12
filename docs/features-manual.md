# [CliToolkit](../README.md) -> Features Manual

Here are more detailed descriptions for different features you may find in the project...

## Contents

- [Parameter types](#parameter-types)
- [Type casting from requests](#type-casting-from-requests)
- [Validators](#validators)
- [Subcommands](#subcommands)

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
$operationRequest = $request->getCommandRequest($operation);
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
