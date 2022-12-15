<?php

declare(strict_types=1);

use GD75\DoubleQuoteFixer\DoubleQuoteFixer;

$config = new PhpCsFixer\Config();

return $config
    ->registerCustomFixers([new DoubleQuoteFixer()])
    ->setUsingCache(false)
    ->setRiskyAllowed(true)
    ->setRules([
        "@Symfony" => true,
        "@Symfony:risky" => true,

        // Overwrite @Symfony rules.
        "single_line_throw" => false,

        // Project specific rules.
        "php_unit_strict" => true,
        "strict_param" => true,
        "declare_strict_types" => true,
        "single_quote" => false,
        "GD75/double_quote_fixer" => true,
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->notName("*Spec.php")
            ->exclude(["vendor", "var", "node_modules"])
            ->in(__DIR__),
    );
