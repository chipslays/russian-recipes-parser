<?php

use Carbon\Carbon;
use Recipes\Cli;
use Illuminate\Database\Capsule\Manager as DB;

require_once __DIR__ . '/bootstrap.php';

Cli::title('СТАРТ: ДОБАВЛЕНИЕ ИНГРЕДИЕНТОВ');

$popularIngredients = json_decode(file_get_contents(__DIR__ . '/storage/ingredients_rating.json'), true);

$ingredients = json_decode(file_get_contents(__DIR__ . '/storage/ingredients_all.json'), true);
foreach ($ingredients as $ingredient) {
    $ingredient['uses'] = $popularIngredients[$ingredient['slug']] ;
    DB::connection('mysql')->table('ingredients')->insert($ingredient);
    Cli::log("Добавлен ингредиент: {$ingredient['name']}");
}

Cli::title('ЗАВЕРШЕНО: ДОБАВЛЕНИЕ ИНГРЕДИЕНТОВ');
Cli::title('СТАРТ: ДОБАВЛЕНИЕ КАТЕГОРИЙ');

$categories = json_decode(file_get_contents(__DIR__ . '/storage/categories.json'), true);
foreach ($categories as $category) {
    DB::connection('mysql')->table('categories')->insert($category);
    Cli::log("Добавлена категория: {$category['name']}");
}

Cli::title('ЗАВЕРШЕНО: ДОБАВЛЕНИЕ КАТЕГОРИЙ');
Cli::title('СТАРТ: ДОБАВЛЕНИЕ ТЕГОВ');

$tags = json_decode(file_get_contents(__DIR__ . '/storage/tags.json'), true);
foreach ($tags as $tag) {
    $tag['similar'] = json_encode($tag['similar'], JSON_UNESCAPED_UNICODE);
    $tag['parent_name'] = $tag['parent']['name'] ?? null;
    $tag['parent_slug'] = $tag['parent']['slug'] ?? null;
    unset($tag['parent']);
    DB::connection('mysql')->table('tags')->insert($tag);
    Cli::log("Добавлен тег: {$tag['name']}");
}

Cli::title('ЗАВЕРШЕНО: ДОБАВЛЕНИЕ ТЕГОВ');
Cli::title('СТАРТ: ДОБАВЛЕНИЕ КУХОНЬ');

$cuisines = json_decode(file_get_contents(__DIR__ . '/storage/cuisines.json'), true);
foreach ($cuisines as $cuisine) {
    DB::connection('mysql')->table('cuisines')->insert($cuisine);
    Cli::log("Добавлена кухня: {$cuisine['name']}");
}

Cli::title('ЗАВЕРШЕНО: ДОБАВЛЕНИЕ КУХОНЬ');
Cli::title('СТАРТ: ДОБАВЛЕНИЕ РЕЦЕПТОВ');

$path = __DIR__ . '/storage/recipes';

$files = glob("{$path}/*/*.json");

$totalFiles = count($files);

Cli::log("Найдено файлов с рецептами: {$totalFiles}");

$errors = [];

foreach ($files as $index => $file) {
    $recipe = json_decode(file_get_contents($file), true);

    // рецепты которые мне не нравятся как оформлены
    $blacklistNames = [
        'Домашняя кулебяка со сложной начинкой',
        'Салат слоеный с курицей, шампиньонами и яйцом',
        '11 начинок для рулетиков из лаваша',
        'Простая закуска «Улитки»',
        'Закусочные улитки из сосисок',
        'Запеченный картофель с начинками в духовке',
    ];
    if (in_array($recipe['title'], $blacklistNames)) {
        continue;
    }

    $currentIndex = $index + 1;

    $categoryId = DB::connection('mysql')->table('categories')->where('slug', $recipe['category_slug'])->first(['id']);

    if (!$categoryId) {
        Cli::log("{$recipe['source']} -> нет категории {$recipe['category']}.", 'warning');
        $errors[] = $recipe['source'];
        continue;
    }
    $categoryId = $categoryId->id;

    $cuisineId = DB::connection('mysql')->table('cuisines')->where('slug', $recipe['cuisine_slug'])->first(['id']);

    if (!$cuisineId) {
        $cuisineId = DB::connection('mysql')->table('cuisines')->where('slug', 'domashnyaya-kuhnya')->first(['id']);
    }
    $cuisineId = $cuisineId->id;

    Cli::log($recipe['source']);

    $recipeId = DB::connection('mysql')->table('recipes')->insertGetId([
        'category_id' => $categoryId,
        'cuisine_id' => $cuisineId,
        'title' => $recipe['title'],
        'description' => $recipe['description'],
        'note' => $recipe['note'],
        'poster' => $recipe['poster'],
        'difficulty' => $recipe['difficulty'],
        'cook_time' => $recipe['cooktime'],
        'prepare_time' => $recipe['preparetime'],
        'video' => $recipe['video'],
        'vegan' => $recipe['vegan'],
        // 'ingredients' => json_encode($recipe['ingredients']),
        'instruction' => json_encode($recipe['instruction']),
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now(),
    ]);

    // DB::connection('mysql')->table('recipe_categories')->insert([
    //     'recipe_id' => $recipeId,
    //     'category_id' => $categoryId,
    // ]);

    foreach ($recipe['ingredients'] as $item) {
        foreach ($item['list'] as $ingredient) {
            if (!$ingredient['name']) {
                continue;
            }

            $ingredientId = DB::connection('mysql')->table('ingredients')->where('slug', $ingredient['slug'])->first(['id'])->id;

            DB::connection('mysql')->table('recipe_ingredients')->insert([
                'recipe_id' => $recipeId,
                'ingredient_id' => $ingredientId,
                'group' => $item['name'],
                'note' => $ingredient['notes'],
                'value' => $ingredient['value'],
                'type' => $ingredient['type'],
                'amount' => $ingredient['amount'],
            ]);
        }
    }

    foreach ($recipe['tags'] as $tag) {
        if ($tag['slug'] == 'poshagovye-foto-recepty') {
            continue;
        }

        $tagId = DB::connection('mysql')->table('tags')->where('slug', $tag['slug'])->first(['id']);

        if (!$tagId) {
            Cli::log("{$recipe['source']} -> нет тега {$tag['slug']}.", 'warning');
            $errors[] = $recipe['source'];
            continue;
        }

        $tagId = $tagId->id;

        DB::connection('mysql')->table('recipe_tags')->insert([
            'recipe_id' => $recipeId,
            'tag_id' => $tagId,
        ]);
    }

    Cli::log("[{$currentIndex}/{$totalFiles}] Добавлен рецепт: {$recipe['title']}");
}

// errors
file_put_contents(__DIR__ . '/storage/errors/db_seed.json', json_encode($errors, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

Cli::title('ЗАВЕРШЕНО: ДОБАВЛЕНИЕ РЕЦЕПТОВ');
