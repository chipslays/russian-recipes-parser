<?php

if (!function_exists('mb_ucfirst')) {
    function mb_ucfirst($string, $enc = 'UTF-8') {
        return mb_strtoupper(mb_substr($string, 0, 1, $enc), $enc) .
            mb_substr($string, 1, mb_strlen($string, $enc), $enc);
    }
}

if (!function_exists('ingr_normalize')) {
    function ingr_normalize($name) {
            $name = str_replace('сливки33%', 'сливки 33%', $name);

            return slugify($name);
    }
}

if (!function_exists('slugify')) {
    function slugify($string) {
        $string = mb_strtolower(trim(preg_replace('/[^A-Za-zА-Яа-яЁё0-9-]+/u', '-', $string), '-'));

        $replaces = [
            'ый' => 'iy',
            'а' => 'a',
            'б' => 'b',
            'в' => 'v',
            'г' => 'g',
            'д' => 'd',
            'е' => 'e',
            'ё' => 'e',
            'ж' => 'j',
            'з' => 'z',
            'и' => 'i',
            'й' => 'y',
            'к' => 'k',
            'л' => 'l',
            'м' => 'm',
            'н' => 'n',
            'о' => 'o',
            'п' => 'p',
            'р' => 'r',
            'с' => 's',
            'т' => 't',
            'у' => 'u',
            'ф' => 'f',
            'х' => 'h',
            'ц' => 'c',
            'ч' => 'ch',
            'ш' => 'sh',
            'щ' => 'sch',
            'ъ' => '',
            'ы' => 'y',
            'ь' => '',
            'э' => 'e',
            'ю' => 'yu',
            'я' => 'ya',
        ];

        return strtr($string, $replaces);
    }
}