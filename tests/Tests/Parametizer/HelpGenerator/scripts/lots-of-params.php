<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Tests\Utils\TestUtils;

require_once __DIR__ . '/../../../init-console.php';

TestUtils::newConfig()
    /*
     * There is excessive padding.
     * Also pay attention at the blank line with 4 spaces under the first paragraph: in previous version it caused
     * the whole help block to be shown as is with such a huge padding. The point is that the length of this exact
     * space-line negatively affected the whole block minimum padding. However now it should be ignored at all,
     * so eventually there should be correct padding for the whole block.
     */
    ->description('
                Here is a very very very very long description.
                So long that multiple lines are needed.
    
                And there is more than that! Here is a list with padding, which should be outputted the same way '
                . 'in a terminal:
                    1. Start with this step.
                    2. Proceed with doing this thing.
                    3. Finally finish by doing this stuff.
                    
                                                    HERE IS SOME MORE PADDED TEXT
              

                                            
    ') // There are some blank lines including the one with spaces. All should be properly trimmed.

    ->usage('--opt-required=5 arg')
    ->usage('--opt-required=5 arg -fg C asd zxc')
    ->usage('--opt-required=5 arg -fg C asd zxc', 'Same usage, but with description')

    ->usage(
        'argument --opt-required=pink --opt-default=cool --flag2 A --opt-list=250 --opt-list=500 -- arg_elem_1 arg_elem_2 --opt=not_option_but_arg_elem3',
        'Usage long example with description',
    )

    ->newOption('--opt-default', '-o')
    ->description('Non-required option with a default value')
    ->default('opt_default_value')

    ->newArrayOption('--opt-list', '-l')
    ->description('List of values')
    ->allowedValues(range(100, 800, 50), true) // Values are hidden from help.

    ->newFlag('--flag1', '-f')
    ->description('Some flag')

    ->newFlag('--flag2', '-g')

    ->newFlag('--flag3')
    ->description('Flag without short name')

    // Let's place it here intentionally - to ensure the options correct order from `HelpGenerator::getParamsBlock()`
    ->newOption('--opt-required')
    ->description('Required option: pick one from the list')
    ->required()
    ->allowedValuesDescribed([
        'black' => 'A pile of books',
        'pink'  => 'A heap of ponies',
        'white' => null, // No description,
        '5'     => 'Give me "five"!',
    ])

    ->newArgument('arg-required')
    ->description('Required argument')

    ->newArgument('arg-optional')
    ->description('Optional argument: pick one from the list')
    ->default('B')
    ->allowedValues(['A', 'B', 'C'], true)
    ->allowedValues(['A', 'B', 'C']) // Making the list of values visible (after the previous statement hiding those).

    ->newArrayArgument('arg-list')
    ->required(false)

    ->run();
