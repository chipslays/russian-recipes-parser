<?php

use Recipes\Cli;
use DiDom\Document;

require_once __DIR__ . '/bootstrap.php';

// Шаг 1. Собираем и обрабатываем теги рецептов
Cli::title('СТАРТ: СБОР ТЕГОВ');

$url = 'https://vkuso.ru/sitemap-tax-course.xml';

if (!$sitemap = file_get_contents($url)) {
    throw new Exception("Ошибка при получении данных тегов.", 1);
}

$sitemapCourse = json_decode(json_encode(simplexml_load_string($sitemap)), true);

$coursesCount = count($sitemapCourse['url']);

$tags = [];
foreach ($sitemapCourse['url'] as $key => $item) {
    $currentIndex = $key + 1;
    Cli::log("[{$currentIndex}/{$coursesCount}] Обработка тега -> {$item['loc']}");

    $tagPage = new Document($item['loc'], true);

    $dishes = $tagPage->find('.dishes-list li a');

    $similar = [];
    foreach ($dishes as $dish) {
        $similar[] = [
            'name' => trim($dish->text()),
            // 'slug' => basename(strtok($dish->href, '?')),
            'slug' => slugify(trim($dish->text())),
        ];
    }

    $parents = $tagPage->find('span[itemprop="itemListElement"]');
    // if (count($parents) == 2) {
    //     continue;
    // }
    $parent = trim($parents[1]->text());

    $tags[] = [
        'name' => $tagPage->first('span.current span[itemprop="name"]')->text(),
        'slug' => slugify($tagPage->first('span.current span[itemprop="name"]')->text()),
        'description' => $tagPage->has('.description-content') ? $tagPage->first('.description-content')->text() : null,
        'parent' => [
            'name' => $parent,
            'slug' => slugify($parent),
        ],
        'similar' => $similar,
    ];

    Cli::log("[{$currentIndex}/{$coursesCount}] Тег добавлен: " . end($tags)['name']);
}

$tags[] = [
    'name' => 'Вторые блюда из фарша',
    'slug' => slugify('Вторые блюда из фарша'),
    'description' => null,
    'parent' => [
        'name' => 'Вторые блюда',
        'slug' => slugify('Вторые блюда'),
    ],
    'similar' => [],
];

file_put_contents(__DIR__ . '/storage/tags.json', json_encode($tags, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

Cli::log("Добавлено тегов: " . count($tags));

Cli::title('ЗАВЕРШЕНО: СБОР ТЕГОВ');