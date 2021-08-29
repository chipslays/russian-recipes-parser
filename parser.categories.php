<?php

use Recipes\Cli;
use DiDom\Document;

require_once __DIR__ . '/bootstrap.php';

// Шаг 1. Собираем и обрабатываем теги рецептов
Cli::title('СТАРТ: СБОР КАТЕГОРИЙ');

$url = 'https://vkuso.ru/sitemap-tax-category.xml';

if (!$sitemap = file_get_contents($url)) {
    throw new Exception("Ошибка при получении данных категорий.", 1);
}

$sitemap = json_decode(json_encode(simplexml_load_string($sitemap)), true);

$count = count($sitemap['url']);

$categories = [];
foreach ($sitemap['url'] as $key => $item) {
    if (strpos($item['loc'], 'https://vkuso.ru/recipes/') === false
        || $item['loc'] == 'https://vkuso.ru/recipes/menu/'
        || $item['loc'] == 'https://vkuso.ru/recipes/molochnye-produkty-domashnie/') {
        continue;
    }

    $currentIndex = $key + 1;
    Cli::log("[{$currentIndex}/{$count}] Обработка категории -> {$item['loc']}");

    $catPage = new Document($item['loc'], true);

    $categories[] = [
        'name' => $catPage->find('span[itemprop="name"]')[1]->text(),
        'slug' => slugify($catPage->find('span[itemprop="name"]')[1]->text()),
        'description' => $catPage->has('div.description-content') ? $catPage->first('div.description-content')->text() : null,
    ];

    Cli::log("[{$currentIndex}/{$count}] Категория добавлена: " . end($categories)['name']);
}

file_put_contents(__DIR__ . '/storage/categories.json', json_encode($categories, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));


Cli::log("Добавлено категорий: " . count($categories));

Cli::title('ЗАВЕРШЕНО: СБОР КАТЕГОРИЙ');