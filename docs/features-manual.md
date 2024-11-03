# [CliToolkit](../README.md) -> Features Manual

Here are more detailed descriptions for different features you may find in the project...

## Contents

- [Parameter types](#parameter-types)
- [Validators](#validators)

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

## Validators

Ensure your script can be called with valid values only.

Configure possible values list:

```php
use MagicPush\CliToolkit\Parametizer\Parametizer;

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
use MagicPush\CliToolkit\Parametizer\Parametizer;

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
use MagicPush\CliToolkit\Parametizer\Parametizer;

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
