<?php

use Recipes\Cli;

require_once __DIR__ . '/bootstrap.php';

$path = __DIR__ . '/storage/recipes';

$files = glob("{$path}/*/*.json");

$totalFiles = count($files);

Cli::log("Найдено файлов с рецептами: {$totalFiles}");

foreach ($files as $index => $file) {
    $recipe = json_decode(file_get_contents($file), true);

    if (count($recipe['ingredients']) == 0) {
        unlink($file);
        Cli::log("[УДАЛЁН] {$recipe['source']} -> нет ингредиентов.");
        $errors[] = $recipe['source'];
        continue;
    }

    if (count($recipe['instruction']) == 0) {
        unlink($file);
        Cli::log("[УДАЛЁН] {$recipe['source']} -> нет инструкции.");
        $errors[] = $recipe['source'];
        continue;
    }
}
