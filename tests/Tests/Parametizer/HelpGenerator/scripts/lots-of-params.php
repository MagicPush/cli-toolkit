<?php declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../init-console.php';

Parametizer::newConfig()
    ->description('
        Here is a very very very very long description.
        So long that multiple lines are needed.
        
        And there is more than that! Here is a list with padding, which should be outputted the same way in a terminal:
            1. Start with this step.
            2. Proceed with doing this thing.
            3. Finally finish by doing this stuff.
            
                                            HERE IS SOME MORE PADDED TEXT
              

                                            
    ') // There are some blank lines including the one with spaces. All should be properly trimmed.

    ->usage('--opt-required=5 arg')
    ->usage('--opt-required=5 arg -fg C asd zxc')
    ->usage('--opt-required=5 arg -fg C asd zxc', 'Same usage, but with description')

    ->usage(
        'argument --opt-required=pink --opt-default=weee --flag2 A --opt-list=250 --opt-list=500 -- arg_elem_1 arg_elem_2 --opt=not_option_but_arg_elem3',
        'Usage long example with description',
    )

    ->newOption('--opt-required')
    ->description('Required option: pick one from the list')
    ->required()
    ->allowedValuesDescribed([
        'black' => 'A pile of books',
        'pink'  => 'A heap of ponies',
        'white' => null, // No description,
        '5'     => 'Give me "five"!',
    ])

    ->newOption('--opt-default', '-o')
    ->description('Non-required option with a default value')
    ->default('opt_default_value')

    ->newArrayOption('--opt-list', '-l')
    ->description('List of values')
    ->allowedValues(range(100, 800, 50))

    ->newFlag('--flag1', '-f')
    ->description('Some flag')

    ->newFlag('--flag2', '-g')

    ->newFlag('--flag3')
    ->description('Flag without short name')

    ->newArgument('arg-required')
    ->description('Required argument')

    ->newArgument('arg-optional')
    ->description('Optional argument: pick one from the list')
    ->default('B')
    ->allowedValues(['A', 'B', 'C'])

    ->newArrayArgument('arg-list')
    ->required(false)

    ->run();
