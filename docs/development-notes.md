# [CliToolkit](../README.md) -> Development notes

Added here mainly for the library maintainers - not to forget decisions made to arguable cases.

## Contents

- [PHPUnit](#phpunit)
    - [Launching test scripts inside PHPUnit processes](#launching-test-scripts-inside-phpunit-processes)
- [Class-based scripts mass tests](#class-based-scripts-mass-tests)
    - [Tokenizer vs RegExp](#tokenizer-vs-regexp)
    - [Scripts detection performance](#scripts-detection-performance)
    - [EnvironmentConfig load performance](#environmentconfig-load-performance)
    - [RegExp in subcommand name validation](#regexp-in-subcommand-name-validation)
- [Throwing or ignoring exceptions default policy](#throwing-or-ignoring-exceptions-default-policy)
- [Simplifying chain calls](#simplifying-chain-calls)
    - [Parametizer::newConfig()](#parametizernewconfig)

## PHPUnit

### Launching test scripts inside PHPUnit processes

It is generally possible, but requires notable library refactoring (including backward incompatibilities).

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
   (as a CLI scripts library).
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
2. Enable caching.

### EnvironmentConfig load performance

Negligible. `--dir-count=50 --dir-max-level=5 2000`:

|                                                               Condition | Seconds | Memory, MB |
|------------------------------------------------------------------------:|:--------|:-----------|
| Autoload OFF: `ScriptClassLauncher::useParentEnvConfigForSubcommands()` | `0.150` | `25.898`   |
|                                            Autoload ON + no config file | `0.185` | `28.914`   |
|           Autoload ON + a config file somewhere in `MassTest` directory | `0.210` | `29.795`   |

### RegExp in subcommand name validation

Removing regexp check in `Config::newSubcommand()` changes nothing on _milliseconds_ scale.

## Throwing or ignoring exceptions default policy

The main question here is what default values should be for the corresponding settings in
[ScriptDetectorAbstract.php](../src/Parametizer/ScriptDetector/ScriptDetectorAbstract.php) descendants
and all scripts' [EnvironmentConfig.php](../src/Parametizer/EnvironmentConfig.php) instances.

The current policy:

1. [EnvironmentConfig.php](../src/Parametizer/EnvironmentConfig.php) default: **silence** exceptions.
    * Considering how damaging `EnvironmentConfig` construction's exception might be (effectively breaking up to all
      console scripts), it is safer to silence those exceptions, until the library user will want to debug exceptions
      in a controlled environment.

      Also, at least for now, the [environment settings](features-manual.md#available-settings) are not so significant
      for scripts' operating to worry about invalid setups.
2. [ScriptDetectorAbstract.php](../src/Parametizer/ScriptDetector/ScriptDetectorAbstract.php) descendants' default:
   **throw** exceptions.
    * A script detector's setup is much more crucial, because its exceptions in most cases mean that some portions of
      (or even all) scripts are not available. So users should know about such exceptions in the first place.

      In case of emergency, if users do not have time to fix their detector setup on production,
      for [ScriptClassAbstract.php](../src/Parametizer/ScriptClass/ScriptClassAbstract.php)-based scripts they can use
      the alternative, manual launcher - [execute-class.php](../tools/cli-toolkit/execute-class.php).
3. Additionally, the launcher skeleton created by
   [LauncherSkeleton.php](../tools/cli-toolkit/ScriptClasses/Generate/LauncherSkeleton.php) will explicitly set
   the same corresponding default values both for a detector and environment configs.
    * Even if the library users do not read the documentation, at the first time they read a generated launcher script,
      they will know about a possibility to silence (or enable) exceptions. And then users decide if they prefer
      a zero-bug or production-safe setup.

## Simplifying chain calls

### `Parametizer::newConfig()`

**Hypothesis**: `EnvironmentConfig` might be automatically created right in `Parametizer::newConfig()`, before
internal calls in the stack reach `Config::__construct()`. Thus, `$throwOnException` parameter, needed (for now) only
for `EnvironmentConfig` automatic creation, may be stripped off in the call stack - only `Parametizer::newConfig()`
should keep this parameter, and `Config` then will require an env config instance only (no extra flag).

**Decision**: this simplification should _not_ be implemented. From the development perspective, it changes
almost nothing - almost no calls become easier in 95% of cases, if the methods' signatures are simplified.
Because in 95% of cases `Parametizer::newConfig()` is called, not the inner methods along the stack.
