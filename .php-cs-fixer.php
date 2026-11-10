<?php

declare(strict_types=1);

use PhpCsFixer\{Config, Finder};
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$finder = (new Finder())->in(__DIR__);

return (new Config())
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setRules([
        '@PER-CS3x0' => true,
        'array_push' => true,
        'backtick_to_shell_exec' => true,
        'date_time_immutable' => true,
        'declare_strict_types' => true,
        'final_class' => true,
        'final_internal_class' => true,
        'final_public_method_for_abstract_class' => true,
        'fully_qualified_strict_types' => true,
        'function_declaration' => [
            'closure_fn_spacing' => 'one',
        ],
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true,
        ],
        'group_import' => true,
        'lowercase_keywords' => true,
        'lowercase_static_reference' => true,
        'mb_str_functions' => true,
        'modernize_types_casting' => true,
        'new_with_parentheses' => [
            'anonymous_class' => false,
            'named_class' => true,
        ],
        'no_multiple_statements_per_line' => true,
        'no_superfluous_elseif' => true,
        'no_unused_imports' => true,
        'no_useless_else' => true,
        'ordered_class_elements' => [
            'order' => [
                'use_trait',
                'case',
                'constant',
                'constant_public',
                'constant_protected',
                'constant_private',
                'property_public',
                'property_protected',
                'property_private',
                'construct',
                'destruct',
                'magic',
                'phpunit',
                'method_abstract',
                'method_public_static',
                'method_public',
                'method_protected_static',
                'method_protected',
                'method_private_static',
                'method_private',
            ],
            'sort_algorithm' => 'none',
        ],
        'ordered_interfaces' => true,
        'ordered_traits' => true,
        'protected_to_private' => true,
        'self_accessor' => true,
        'self_static_accessor' => true,
        'single_import_per_statement' => false,
        'strict_comparison' => true,
        'visibility_required' => true,
        'yoda_style' => [
            'always_move_variable' => true,
        ],
    ])
    ->setParallelConfig(ParallelConfigFactory::detect());
