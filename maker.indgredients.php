<?php

use Recipes\Cli;

require_once __DIR__ . '/bootstrap.php';

$path = __DIR__ . '/storage/recipes';

$files = glob("{$path}/*/*.json");

$ingredients = [];
$totalFiles = count($files);
$_alreadyAdded = [];
$_rating = [];
$errors = [];

Cli::log("Найдено файлов с рецептами: {$totalFiles}");

foreach ($files as $index => $file) {
    $recipe = json_decode(file_get_contents($file), true);

    $currentIndex = $index + 1;

    if (count($recipe['ingredients']) == 0) {
        Cli::log("[{$currentIndex}/{$totalFiles}] {$recipe['source']} -> нет ингредиентов.", 'warning');
        $errors[] = $recipe['source'];
        continue;
    }

    if (count($recipe['instruction']) == 0) {
        Cli::log("[{$currentIndex}/{$totalFiles}] {$recipe['source']} -> нет инструкции.", 'warning');
        $errors[] = $recipe['source'];
        continue;
    }

    foreach ($recipe['ingredients'] as $item) {
        foreach ($item['list'] as $ingredient) {
            if (!$ingredient['name']) {
                continue;
            }

            $name = mb_strtolower($ingredient['name']);

            // нормализация опечаток
            $slug = ingr_normalize($name);

            if (array_key_exists($slug, $_rating)) {
                $_rating[$slug]++;
            } else {
                $_rating[$slug] = 1;
            }

            if (in_array($slug, $_alreadyAdded)) {
                continue;
            }

            $_alreadyAdded[] = $slug;

            $ingredients[] = [
                'name' => $name,
                'slug' => $slug,
            ];

            Cli::log("[{$currentIndex}/{$totalFiles}] Добавлено: {$name} -> {$slug}");
        }
    }

}

file_put_contents(__DIR__ . '/storage/ingredients_all.json', json_encode($ingredients, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

$popular = collection($_rating)->filter(function ($value, $slug) {
    $blacklist = [
        'uksusa', 'svininy', 'mayoneza', // и т.п...
    ];

    return $value >= 10 && !in_array($slug, $blacklist);
})->toArray();

arsort($popular);
arsort($_rating);

file_put_contents(__DIR__ . '/storage/ingredients_popular.json', json_encode($popular, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
file_put_contents(__DIR__ . '/storage/ingredients_rating.json', json_encode($_rating, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

// errors
file_put_contents(__DIR__ . '/storage/errors/ingredients.json', json_encode($errors, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));