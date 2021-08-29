<?php

/**
 * Больше не актуален.
 * Патч исправлял пустой текст у инструкций с картинками в json файлах.
 */

use DiDom\Document;
use Recipes\Cli;

require_once __DIR__ . '/bootstrap.php';

$path = __DIR__ . '/storage/recipes';

$files = glob("{$path}/*/*.json");

$ingredients = [];
$totalFiles = count($files);
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

    Cli::log("[{$currentIndex}/{$totalFiles}] Проверка -> {$recipe['source']}");

    foreach ($recipe['instruction'] as $step) {
        if (trim($step['text']) !== '') {
            continue;
        }

        $detailPage = new Document($recipe['source'], true);

        $recipe['instruction'] = [];
        foreach ($detailPage->find('li.instruction') as $instruction) {
            $image = $instruction->has('img') ? $instruction->first('img')->getAttribute('data-src') : null;
            if ($image && strpos($image, 'http') == false) {
                $image = str_replace('//', 'https://', $image);
            }

            if ($detailPage->has('.instructions.ver_2')) {
                $recipe['instruction'][] = [
                    'text' => $instruction->find('div.instruction_description')[0]->text(),
                    'image' => $image,
                ];
            } else {
                if (!$child = $instruction->child(0)) {
                    continue;
                }

                $recipe['instruction'][] = [
                    'text' => $child->text(),
                    'image' => $image,
                ];
            }
        }

        file_put_contents($file, json_encode($recipe, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        Cli::log("[{$currentIndex}/{$totalFiles}] Fixed -> {$recipe['source']}", 'warning');
        break;
    }
}
