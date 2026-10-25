<?php

declare(strict_types=1);

// Run examples from either the library checkout or a Composer-installed package.
foreach ([__DIR__ . '/../vendor/autoload.php', dirname(__DIR__, 3) . '/autoload.php'] as $autoload) {
    if (is_file($autoload)) {
        require_once $autoload;

        return;
    }
}

throw new RuntimeException('Composer autoload not found. Run composer install in the library checkout.');
