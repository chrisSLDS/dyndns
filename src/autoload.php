<?php

spl_autoload_register(function ($class) {
    // Convert namespace separators to directory separators
    $file = __DIR__ . '/' . str_replace('\\', '/', $class) . '.php';
    
    // Check if the file exists
    if (file_exists($file)) {
        require_once $file;
        return true;
    }
    
    return false;
});