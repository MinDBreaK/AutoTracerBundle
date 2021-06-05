<?php

/*
 * This file is part of the AutoTracerBundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$header = <<<HEADER
This file is part of the AutoTracerBundle package.

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
HEADER;


$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
    ->exclude('vendor');

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12'                     => true,
        'mb_str_functions'           => true,
        'native_function_invocation' => [
            'exclude' => [],
            'include' => ['@compiler_optimized'],
            'scope'   => 'all',
            'strict'  => true,
        ],
        'header_comment' => [
            'header' => $header,
        ],
        'no_unused_imports'      => true,
        'binary_operator_spaces' => [
            "default" => "align_single_space_minimal",
        ]
    ])
    ->setFinder($finder);
