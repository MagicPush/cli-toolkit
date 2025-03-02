# CliToolkit

[![Latest stable version](https://img.shields.io/packagist/v/magic-push/cli-toolkit?label=version)](https://packagist.org/packages/magic-push/cli-toolkit)

**CliToolkit** is a CLI framework for PHP scripts.

Key features (why you would want to use it):
- configure named (options) and positioned (arguments) parameters with ease using a builder;
- define required options, optional arguments, lists of possible values, flags, array-like parameters and subcommands;
- call your scripts from any paths by generated aliases;
- enjoy autocompleting options' names and parameters' possible values (when calling scripts via special aliases);
- get a generated help page (using the built-in `--help` option) based on your parameters configuration.

## Contents

- [Installation](#installation)
- [How to](#how-to)
- [Examples](#examples)
- [Inspiration and authors](#inspiration-and-authors)
- [More info](#more-info)

## Installation

```shell
composer require magic-push/cli-toolkit
```

## How to

Just create a php-file and start configuring:
```php
// Configure your script parameters
$request = Parametizer::newConfig()
    ->newArgument('chunk-size') // A positioned parameter.
    ->newFlag('--dry-run') // A named boolean parameter.

    ->run();

// Read parameters
$chunkSize = $request->getParamAsInt('chunk-size');

// Process...

if (!$request->getParamAsBool('dry-run')) {
    // Make data changes.
}
```

If you want to read your script's documentation, then just call your script with the `--help` option:
```
$ path/to/my-cool-script.php --help

USAGE

  my-cool-script.php [--dry-run] <chunk-size>

OPTIONS

  --dry-run

  --help      Show full help page.

ARGUMENTS

  <chunk-size>
  (required)
```

Config and parameter builders will guide you with available options you can set up. If you set something odd, then
built-in validators will show you corresponding errors:

```php
$request = Parametizer::newConfig()
    ->newArgument('chunk-size')
    ->default(100)
    ->required()

    ->run();
```

```
$ my-cool-script.php
'chunk-size' >>> Config error: a parameter can't be required and have a default simultaneously.
```

For more cool stuff to know see [Features Manual](docs/features-manual.md).

## Examples

Here are [useful scripts](tools/cli-toolkit/Scripts)
that also utilize some Parametizer features (so may be studied as examples).

- [GenerateAutocompletionScript.php](tools/cli-toolkit/Scripts/GenerateAutocompletionScript.php):
  you should start with this script, as it enables the autocompletion for all Parametizer-powered scripts.
    - Launch the script and read the details:
      ```sh
      php tools/cli-toolkit/launcher.php generate-autocompletion-script --verbose
      ```
    - Read it's manual for further customization:
      ```sh
      php tools/cli-toolkit/launcher.php generate-autocompletion-script --help
      ```
- [GenerateEnvConfig.php](tools/cli-toolkit/Scripts/GenerateEnvConfig.php):
  helps to configure environment settings for your scripts.
  See [Environment Config manual](docs/features-manual.md#environment-config) for details.
- [TerminalFormatterShowcase.php](tools/cli-toolkit/Scripts/TerminalFormatterShowcase.php):
  this script shows examples and codes for a terminal coloring and formatting by utilizing
  the [TerminalFormatter](src/TerminalFormatter.php) class included in the project. See usage:
  ```sh
  php tools/cli-toolkit/launcher.php terminal-formatter-showcase --help
  ```

You can also look through `/*/scripts/*` files in [Tests](tests/Tests) directory as artificial examples.

## Inspiration and authors

**CliToolkit** was inspired by and based on [Cliff](https://github.com/johnnywoo/cliff) project, so the first author is
[Aleksandr Galkin](https://github.com/johnnywoo).

A part of ideas and code for [CliToolkit v1.0.0](docs/changelog.md#v100) was brought by
[Anton Kotik](https://github.com/anton-kotik).

The [Question](src/Question/Question.php) class was developed by [Vasiliy Borodin](https://github.com/borodin-vasiliy).

The rest is done by Kirill "Magic Push" Ulanovskii.

## More info

- [Features Manual](docs/features-manual.md) - the "[How to](#how-to)" continuation.
- [TODO](docs/todo.md) - the list of things I think would be cool to implement here.
- [Changelog](docs/changelog.md)
