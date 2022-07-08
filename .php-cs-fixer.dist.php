<?php

use PhpCsFixerCustomFixers\Fixer\MultilinePromotedPropertiesFixer;

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
;

return (new PhpCsFixer\Config())
    ->registerCustomFixers(new PhpCsFixerCustomFixers\Fixers())
    ->setRules([
        '@PHP81Migration' => true,
        '@Symfony' => true,
        '@Symfony:risky' => false,
        'phpdoc_align' => false,
        'no_superfluous_phpdoc_tags' => false,
        'array_syntax' => ['syntax' => 'short'],
        MultilinePromotedPropertiesFixer::name() => true,
    ])
    ->setFinder($finder)
    ;
