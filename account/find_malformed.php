<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$compiler = app('blade.compiler');
$dir = new RecursiveDirectoryIterator('resources/views');
$ite = new RecursiveIteratorIterator($dir);
foreach ($ite as $file) {
    if ($file->getExtension() === 'php') {
        try {
            $compiler->compileString(file_get_contents($file->getPathname()));
        } catch (\Exception $e) {
            echo 'Malformed file: ' . $file->getPathname() . PHP_EOL;
            echo $e->getMessage() . PHP_EOL;
            break;
        }
    }
}
