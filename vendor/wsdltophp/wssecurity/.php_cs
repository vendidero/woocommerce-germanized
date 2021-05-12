<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('vendor')
    ->in(__DIR__);

return PhpCsFixer\Config::create()
    ->setUsingCache(false)
    ->setRules(array(
        '@PSR2' => true,
        '@PhpCsFixer' => true,
        'array_syntax' => [
            'syntax' => 'short',
        ],
        'binary_operator_spaces' => true,
        'no_whitespace_in_blank_line' => true,
        'ternary_operator_spaces' => true,
        'cast_spaces' => true,
        'trailing_comma_in_multiline_array' => true,
        'concat_space' => [
            'spacing' => 'one',
        ],
        'blank_line_before_statement' => [
            'statements' => [
                'return',
                'declare',
            ],
        ],
        'php_unit_test_class_requires_covers' => false,
    ))
    ->setFinder($finder);
