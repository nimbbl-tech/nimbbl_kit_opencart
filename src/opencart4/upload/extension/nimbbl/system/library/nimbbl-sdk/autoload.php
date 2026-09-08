<?php
/**
 * Nimbbl PHP SDK — simple PSR-4 autoloader.
 * Namespace prefix : Nimbbl\Api\
 * Base directory   : this file's own dir + /src/
 */
spl_autoload_register(function (string $class): void {
    $prefix = 'Nimbbl\\Api\\';
    $len    = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, $len));
    $file     = __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . $relative . '.php';

    if (file_exists($file)) {
        require $file;
    }
});
