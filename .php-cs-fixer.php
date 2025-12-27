<?php

declare(strict_types=1);

use PhpCsFixer\{Config, Finder};
use RVxLab\PhpCsFixerRules\{RuleSet, RuleSetRisky};
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$finder = Finder::create()
    ->in(__DIR__);

return (new Config())
    ->setFinder($finder)
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->registerCustomRuleSets([
        new RuleSet(),
        new RuleSetRisky(),
    ])
    ->setRiskyAllowed(true)
    ->setRules([
        RuleSet::NAME => true,
        RuleSetRisky::NAME => true,
    ]);
