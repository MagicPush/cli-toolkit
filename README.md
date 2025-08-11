# CliToolkit

[![Latest stable version](https://img.shields.io/packagist/v/magic-push/cli-toolkit?label=version)](https://packagist.org/packages/magic-push/cli-toolkit)

**CliToolkit** is a CLI-oriented library for PHP scripts.

Key features (why you would want to use it):
- create console scripts as [plain php-files](#plain-scripts)
  or classes (a classes' [launcher generator is provided](#script-classes));
- configure named (options) and positioned (arguments) parameters with ease using a config builder;
- define required options, optional arguments, lists of possible values, flags, array-like parameters and subcommands;
- enjoy [Bash completion](#completion) for options' names and parameters' possible values, when calling scripts via
  generated aliases; additionally, call your scripts from any path by those generated aliases;
- see generated help pages (using the built-in `--help` option) for your scripts based on your parameters configuration;
- zero dependencies (apart from PHP itself).

## Contents

- [Installation](#installation)
- [How to](#how-to)
    - [Plain scripts](#plain-scripts)
    - [Completion](#completion)
    - [Script classes](#script-classes)
    - [More configuration examples](#more-configuration-examples)
- [CliToolkit values](#clitoolkit-values)
- [Inspiration and authors](#inspiration-and-authors)
- [More info](#more-info)

## Installation

```shell
composer require magic-push/cli-toolkit
```

## How to

### Plain scripts

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

If you want to read your script's documentation, then just call your script with `--help` option:
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
'chunk-size' >>> Config error: a parameter can't be required and have a default value simultaneously.
```

### Script classes

Generate a skeleton for your future class-based scripts:

```shell
php tools/cli-toolkit/run.php cli-toolkit:generate:launcher-skeleton
```

Read the output and comments in generated files.

### Completion

Generate a completion Bash script with aliases to your `Parametizer`-based scripts:

- (as an example) Enable completion for the library stock launcher. Execute the command below and read the output:

  ```shell
  php tools/cli-toolkit/run.php cli-toolkit:generate:completion-script \
      --search-directory-recursive=tools/cli-toolkit \
      --verbose
  ```
- Read the script's help page for parameter details and customize the command to detect your scripts:

  ```shell
  php tools/cli-toolkit/run.php cli-toolkit:generate:completion-script --help
  ```

### More configuration examples

Check out the [stock class scripts](tools/cli-toolkit/ScriptClasses) (see `setUpConfig()`) as examples of class-based
scripts, and `/**/scripts/*` files in [Tests](tests/Tests) subdirectories as artificial examples of plain scripts.

## CliToolkit values

This library is being developed while keeping in mind these values:

- Scripts' development and maintenance should be quick and simple. The library users may focus on their scripts' unique
  logic instead of parameters or infrastructure management. The library builders and generators cover the latter.
- No dependencies apart from PHP and its standard modules. Updating the library should be a simple task:
  no dependencies - no extra libraries to worry about.

  The only tough part here is moving to the next major version.

## Inspiration and authors

**CliToolkit** was inspired by and based on [Cliff](https://github.com/johnnywoo/cliff) project, so the first author is
[Aleksandr Galkin](https://github.com/johnnywoo).

A part of ideas and code for [CliToolkit v1.0.0](docs/changelog.md#v100) was brought by
[Anton Kotik](https://github.com/anton-kotik).

`Question` class was developed by [Vasiliy Borodin](https://github.com/borodin-vasiliy).

The rest is done by Kirill "Magic Push" Ulanovskii.

## More info

- [Features Manual](docs/features-manual.md) - more cool stuff to know about in details.
- [TODO](docs/todo.md) - the list of things I think would be cool to implement in the library.
- [Changelog](docs/changelog.md) - if you want to update the library safely.
