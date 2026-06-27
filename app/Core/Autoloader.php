<?php

namespace App\Core;

class Autoloader {
    public static function register() {
        spl_autoload_register(function ($class) {
            // Project-specific namespace prefix
            $prefix = 'App\\';

            // Base directory for the namespace prefix
            $baseDir = dirname(__DIR__) . '/';

            // Does the class use the namespace prefix?
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                // No, move to the next registered autoloader
                return;
            }

            // Get the relative class name
            $relativeClass = substr($class, $len);

            // Replace namespace separators with directory separators in the relative class name, append .php
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

            // If the file exists, require it
            if (file_exists($file)) {
                require $file;
            }
        });
    }
}
