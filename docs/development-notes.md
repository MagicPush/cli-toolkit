# [CliToolkit](../README.md) -> Development notes

## Contents

- [PHPUnit](#phpunit)
    - [Launching test scripts inside PHPUnit processes](#launching-test-scripts-inside-phpunit-processes)
- [Class-based scripts mass tests](#class-based-scripts-mass-tests)
    - [Tokenizer vs RegExp](#tokenizer-vs-regexp)
    - [Scripts detection performance](#scripts-detection-performance)
    - [EnvironmentConfig load performance](#environmentconfig-load-performance)
    - [RegExp in subcommand name validation](#regexp-in-subcommand-name-validation)
    
## PHPUnit

### Launching test scripts inside PHPUnit processes

It is generally possible, but requires notable library refactoring.

#### Now

All test cases are based on external script launches:

1. Launch a script (optionally) with some parameters.
1. Assert exit code.
1. Assert `STDOUT` and/or `STDERR` contents.
    * STDERR contents are based on naturally thrown exceptions or `set_exception_handler()` setup.
    
#### Main issue

0% of coverage - because the actual code is launched in a separate process, so xdebug "does not see" actual
function calls.

#### Possible solution

Test scripts could be launched within the same PHPUnit process (for instance, by including a script file),
but it would require:

1. Rewriting several `$_SERVER` elements before each script launch, because `Parametizer` naturally relies on those
   (as a CLI scripts framework).
1. Rewriting the library code in places with `exit` calls (_if it is a test environment, then do this..._),
   so a PHPUnit process would continue its job.
1. Catching / expecting `STDOUT` and `STDERR` or rewriting the library to support setting up interfaces
   for output and error streams.

## Class-based scripts mass tests

See [GenerateMassTestScripts.php](../tools/cli-toolkit/ScriptClasses/Internal/GenerateMassTestScripts.php)

### Tokenizer vs RegExp

Comparison between `PhpToken::tokenize` and `preg_match` in
[ScriptClassDetector.php](../src/Parametizer/ScriptDetector/ScriptClassDetector.php) filtering:
- Same memory usage (MB).
- Tokenizer is 20% slower than regexp.

The tokenizer filtering code stored for the safe keeping:
<details>
<summary>(show)</summary>

```php
$fileNamespace            = null;
$fileClassName            = null;
$isTokenDetectedNamespace = false;
$isTokenDetectedClass     = false;
foreach (PhpToken::tokenize($fileContents) as $fileToken) {
    if (T_ABSTRACT === $fileToken->id) {
        break;
    }

    if ($fileToken->isIgnorable()) {
        continue;
    }

    if (null === $fileNamespace) {
        if ($isTokenDetectedNamespace && T_NAME_QUALIFIED === $fileToken->id) {
            $fileNamespace = $fileToken->text;
        } elseif (T_NAMESPACE === $fileToken->id) {
            $isTokenDetectedNamespace = true;
        }
    }

    if (null === $fileClassName) {
        if ($isTokenDetectedClass && T_STRING === $fileToken->id) {
            $fileClassName = $fileToken->text;

            // Nothing useful for us below this token,
            // e.g. 'namespace' can (should) not be defined below a class declaration.
            break;
        } elseif (T_CLASS === $fileToken->id) {
            $isTokenDetectedClass = true;
        }
    }
}
```
</details>

### Scripts detection performance

In large projects searching for scripts recursively in the project's main directory may last for a few seconds
or even dozens of seconds.

Possible solutions:
1. Specify paths that are "closer" to actual scripts (less directories and files to parse).
2. Support caching that may be enabled when needed.

### EnvironmentConfig load performance

Negligible. `--dir-count=50 --dir-max-level=5 2000`:

|                                                           Condition | Seconds | Memory, MB |
|--------------------------------------------------------------------:|:--------|:-----------|
|                                        Autoload ON + no config file | `0.183` | `27.041`   |
|                 Autoload ON + a config file in `MassTest` directory | `0.2`   | `27.041`   |
| Autoload OFF - `ScriptLauncher::useParentEnvConfigForSubcommands()` | `0.14`  | `25.313`   |

### RegExp in subcommand name validation

Removing regexp check in `Config::newSubcommand()` changes nothing on _milliseconds_ scale.
