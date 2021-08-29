<?php

use Recipes\Cli;
use DiDom\Document;
use Chipslays\Collection\Collection;
use Illuminate\Database\Capsule\Manager as DB;

require_once __DIR__ . '/bootstrap.php';

// Шаг 1: Собираем из карты сайта все ссылки с рецептами.
Cli::title('СТАРТ: СБОР КАРТ САЙТОВ С РЕЦЕПТАМИ');

$url = 'https://vkuso.ru/sitemap.xml';

if (!$sitemap = file_get_contents($url)) {
    throw new Exception("Ошибка при получении данных карты сайта.", 1);
}

$sitemap = json_decode(json_encode(simplexml_load_string($sitemap)), true);

$recipesSitemaps = new Collection;


foreach ($sitemap['sitemap'] as $item) {

    if (strpos($item['loc'], 'https://vkuso.ru/sitemap-pt-recipe-') === false) {
        continue;
    }

    if (DB::table('links')->where('link', $item['loc'])->exists()) {
        $link = DB::table('links')->where('link', $item['loc'])->first();
        if ($link->lastmod == strtotime($item['lastmod'])) {
            Cli::log("{$item['loc']} уже есть в базе, данные не обновлялись...", 'warning');
            continue;
        }
    }

    $recipesSitemaps->push($item);

    Cli::log("{$item['loc']} добавлен в очередь на обработку...");
}

Cli::title('ЗАВЕРШЕНО: СБОР КАРТ САЙТОВ С РЕЦЕПТАМИ');

$totalCountSitemapRecipes = $recipesSitemaps->count();

Cli::log("Карты сайтов с рецептами в очереди на обработку: " . $totalCountSitemapRecipes);

// Шаг 2.
if ($totalCountSitemapRecipes <= 0) {
    Cli::log('В очереди нет карт сайтов с детальными страницами рецептов...', 'warning');
    Cli::log('Завершение работы...', 'warning');
    exit;
}

Cli::title('СТАРТ: ОБРАБОТКА ДАННЫХ С ДЕТАЛЬНОЙ СТРАНИЦЫ');
$data = [];
$dataNumber = 1;

foreach ($recipesSitemaps->toArray() as $currentSitemapIndex => $item) {
    $tmpCurrentSitemapIndex = $currentSitemapIndex + 1;

    $recipeSitemap = file_get_contents($item['loc']);
    $recipeSitemap = json_decode(json_encode(simplexml_load_string($recipeSitemap)), true);

    Cli::log("[{$tmpCurrentSitemapIndex}/{$totalCountSitemapRecipes}] Обработка карты сайта детальных страниц -> {$item['loc']}");

    foreach ($recipeSitemap['url'] as $recipe) {
        // обход когда в карте сайта только одна ссылка
        if (is_string($recipe)) {
            $recipe = [
                'loc' => $recipeSitemap['url']['loc'],
                'lastmod' => $recipeSitemap['url']['lastmod'],
            ];
        }

        if (DB::table('links')->where('link', $recipe['loc'])->exists()) {
            $link = DB::table('links')->where('link', $recipe['loc'])->first();
            if ($link->lastmod == strtotime($recipe['lastmod'])) {
                Cli::log("{$recipe['loc']} уже есть в базе, данные не обновлялись...", 'warning');
                continue;
            }
        }

        // Начинаем процесс сбора данных со детальной страницы рецепта

        Cli::log("Обработка -> {$recipe['loc']}");
        $detailPage = new Document($recipe['loc'], true);
        // $detailPage = new Document('https://vkuso.ru/recipe/100123-italyanskij-biskvitnyj-limonnyj-pirog-12-lozhek/', true);

        $insert = [];

        $insert['source'] = $recipe['loc'];
        $insert['category'] = $detailPage->has('span.category') ? $detailPage->first('span.category')->text() : null;
        $insert['category_slug'] = $detailPage->has('span.category') ? slugify($detailPage->first('span.category')->text()) : null;
        $insert['title'] = explode(', рецепт с', $detailPage->first('title')->text())[0];
        $insert['description'] = $detailPage->has('div.recipe_desc') ? $detailPage->first('div.recipe_desc')->text() : null;
        $insert['note'] = $detailPage->has('div.recipe-notes p') ? $detailPage->first('div.recipe-notes p')->text() : null;
        $insert['cuisine'] = $detailPage->first('div.recipe-cuisine span')->text();
        $insert['cuisine_slug'] = slugify($detailPage->first('div.recipe-cuisine span')->text());
        $insert['poster'] = $detailPage->first('img.result-photo.photo')->getAttribute('src');
        $insert['difficulty'] = $detailPage->has('div.recipe-difficulty span') ? $detailPage->first('div.recipe-difficulty span')->text() : null;
        $insert['cooktime'] = $detailPage->has('.recipe_info__item_cook span') ? $detailPage->first('.recipe_info__item_cook span')->text() : null;
        $insert['preparetime'] = $detailPage->has('.recipe_info__item_prep span') ? $detailPage->first('.recipe_info__item_prep span')->text() : null;
        $insert['video'] = $detailPage->has('iframe') ? $detailPage->first('iframe')->getAttribute('src') : null;
        $insert['vegan'] = preg_match('~веганс~ui', $detailPage->first('.article-tags')->text()) || preg_match('~вегетариан~ui', $detailPage->first('.article-tags')->text());

        $insert['ingredients'] = [];
        foreach ($detailPage->find('.recipe-ingr ul') as $key => $ul) {
            foreach ($ul->find('li.ingredient') as $ingredient) {
                $insert['ingredients'][$key]['name'] = $key == 0
                    ? mb_ucfirst($detailPage->first('.recipe-ingr h2')->text())
                    : mb_ucfirst(str_replace(':', '', $detailPage->find('.recipe-ingr h4')[$key - 1]->text()));

                $insert['ingredients'][$key]['list'][] = [
                    'name' => $ingredient->has('span.name') ? $ingredient->first('span.name')->text() : null,
                    'slug' => $ingredient->has('span.name') ? ingr_normalize($ingredient->first('span.name')->text()) : null,
                    'notes' => $ingredient->has('span.notes') ? $ingredient->first('span.notes')->text() : null,
                    'value' => $ingredient->has('span.value') ? $ingredient->first('span.value')->text() : null,
                    'type' => $ingredient->has('span.type') ? $ingredient->first('span.type')->text() : null,
                    'amount' => $ingredient->has('span.amount') ? $ingredient->first('span.amount')->text() : null,
                ];
            }
        }

        $insert['instruction'] = [];
        foreach ($detailPage->find('li.instruction') as $instruction) {
            $image = $instruction->has('img') ? $instruction->first('img')->getAttribute('data-src') : null;
            if ($image && strpos($image, 'http') == false) {
                $image = str_replace('//', 'https://', $image);
            }

            if ($detailPage->has('.instructions.ver_2')) {
                $insert['instruction'][] = [
                    'text' => $instruction->find('div.instruction_description')[0]->text(),
                    'image' => $image,
                ];
            } else {
                if (!$child = $instruction->child(0)) {
                    continue;
                }

                $insert['instruction'][] = [
                    'text' => $child->text(),
                    'image' => $image,
                ];
            }
        }

        $insert['tags'] = [];
        foreach ($detailPage->find('.article-tags a') as $tag) {
            if (substr_count($tag->href, '/') > 5) {
                continue;
            }

            if ($tag->title == 'Видео рецепты') {
                continue;
            }

            $insert['tags'][] = [
                'name' => $tag->title,
                'slug' => slugify($tag->title),
                // 'slug' => str_replace('/', '', str_replace('https://vkuso.ru/recipes/', '', $tag->href)),
            ];
        }

        // сохранение данных в файл
        preg_match('/post_ID":"(.+?)"/u', $detailPage->html(), $matches);
        $postId = $matches[1] ?? '_' . md5($recipe['loc']);

        $dir = substr($postId, 0, 3);
        $path =  __DIR__ . "/storage/recipes/{$dir}";
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
        $filename = "{$path}/{$postId}.json";

        file_put_contents(
            $filename,
            json_encode($insert, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        // добавление/обновление информации о спрашенной ссылке
        if (DB::table('links')->where('link', $recipe['loc'])->exists()) {
            DB::table('links')->where('link', $recipe['loc'])->update([
                'link' => $recipe['loc'],
                'lastmod' => strtotime($recipe['lastmod']),
            ]);
        } else {
            DB::table('links')->insert([
                'link' => $recipe['loc'],
                'lastmod' => strtotime($recipe['lastmod']),
            ]);
        }

        Cli::log("[{$tmpCurrentSitemapIndex}/{$totalCountSitemapRecipes}] {$recipe['loc']} «{$insert['title']}» добавлено в базу...");
    }

    // добавление/обновление информации о спрашенной ссылке
    if (DB::table('links')->where('link', $item['loc'])->exists()) {
        DB::table('links')->where('link', $item['loc'])->update([
            'link' => $item['loc'],
            'lastmod' => strtotime($item['lastmod']),
        ]);
    } else {
        DB::table('links')->insert([
            'link' => $item['loc'],
            'lastmod' => strtotime($item['lastmod']),
        ]);
    }

    Cli::log("[{$tmpCurrentSitemapIndex}/{$totalCountSitemapRecipes}] Обработана карта сайта с детальными страницами -> {$item['loc']}");
}

Cli::title('ЗАВЕРШЕНО: ОБРАБОТКА ДАННЫХ С ДЕТАЛЬНОЙ СТРАНИЦЫ');