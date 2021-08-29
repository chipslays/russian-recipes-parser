# Кулинарные рецепты

Да, кто-то уже за вас спарсил данные с кулинарных сайтов и разложил по json файлам, пользуйтесь.

> На данный момент ~14k рецептов, ~3.8k тегов.

## Готовые файлы

Готовые файлы с рецептами можно найти [`здесь`](storage/recipes).

Теги, ингредиенты и т.п. можно найти [`здесь`](storage).

## Что с этим делать?

Как вариант, закинуть данные в БД, либо использовать сырые файлы 🤷‍♂️

## Использование

Собрать теги.

```bash
php manager parse:tags
```

Собрать рецепты (собранные ранее ссылки игнорируются).

```bash
php manager parse:recipes
```

Очистить ранее собранные ссылки из БД и собрать заново.

```bash
php manager parse:recipes clear:links
```

Удалить битые рецепты.

```bash
php manager normalize
```

Собрать все ингредиенты с спаршенных рецептов.

```bash
php manager make:ingredients
```

Сбросить сохраненные ссылки в БД.

```bash
php manager clear:links
```

Можно комбинировать параметры, порядок не важен.

```bash
php manager parse:tags clear:links parse:recipes normalize make:ingredients
```

## Credits

- [Chipslays](https://github.com/chipslays)
- [All Contributors](../../contributors)

# License

MIT