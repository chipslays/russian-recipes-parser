<?php

use Recipes\Cli;
use DiDom\Document;

require_once __DIR__ . '/bootstrap.php';

Cli::title('СТАРТ: СБОР КУХОНЬ');

$url = 'https://vkuso.ru/sitemap-tax-cuisine.xml';

if (!$sitemap = file_get_contents($url)) {
    throw new Exception("Ошибка при получении данных категорий.", 1);
}

$sitemap = json_decode(json_encode(simplexml_load_string($sitemap)), true);

$count = count($sitemap['url']);

$cuisines = [];
foreach ($sitemap['url'] as $key => $item) {
    $currentIndex = $key + 1;
    Cli::log("[{$currentIndex}/{$count}] Обработка кухни -> {$item['loc']}");

    $catPage = new Document($item['loc'], true);

    $cuisines[] = [
        'name' => $catPage->find('span[itemprop="name"]')[1]->text(),
        'slug' => slugify($catPage->find('span[itemprop="name"]')[1]->text()),
        'description' => $catPage->has('div.description-content') ? $catPage->first('div.description-content')->text() : null,
    ];

    file_put_contents(__DIR__ . '/storage/cuisines.json', json_encode($cuisines, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    Cli::log("[{$currentIndex}/{$count}] Кухня добавлена: " . end($cuisines)['name']);
}

$cuisines[] = [
    'name' => 'Домашняя кухня',
    'slug' => slugify('Домашняя кухня'),
    'description' => 'Домашнюю кухню можно смело назвать самой лучшей. Это поистине мировая кухня, причём во всех смыслах этого слова.',
];

file_put_contents(__DIR__ . '/storage/cuisines.json', json_encode($cuisines, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

Cli::log("Добавлено кухня: " . count($cuisines));

Cli::title('ЗАВЕРШЕНО: СБОР КУХОНЬ');