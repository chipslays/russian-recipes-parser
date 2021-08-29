<?php

namespace Recipes;

use stdClass;

class Cli
{
    /**
     * Colorize text.
     *
     * @param string|int $text
     * @return string
     */
    public static function paint($text = '')
    {
        $list = [
            "{reset}" => "\e[0m",
            "{black}" => "\e[0;30m",
            "{white}" => "\e[1;37m",
            "{dark_grey}" => "\e[1;30m",
            "{dark_gray}" => "\e[1;30m",
            "{light_grey}" => "\e[0;37m",
            "{light_gray}" => "\e[0;37m",
            "{red}" => "\e[0;31m",
            "{light_red}" => "\e[1;31m",
            "{green}" => "\e[0;32m",
            "{light_green}" => "\e[1;32m",
            "{brown}" => "\e[0;33m",
            "{yellow}" => "\e[1;33m",
            "{blue}" => "\e[0;34m",
            "{magenta}" => "\e[0;35m",
            "{light_magenta}" => "\e[1;35m",
            "{cyan}" => "\e[0;36m",
            "{light_cyan}" => "\e[1;36m",
            "{bg:black}" => "\e[40m",
            "{bg:red}" => "\e[41m",
            "{bg:green}" => "\e[42m",
            "{bg:yellow}" => "\e[43m",
            "{bg:blue}" => "\e[44m",
            "{bg:magenta}" => "\e[45m",
            "{bg:cyan}" => "\e[46m",
            "{bg:light_grey}" => "\e[47m",
            "{bg:light_gray}" => "\e[47m",
        ];

        return strtr($text, $list);
    }

    public static function line($text)
    {
        echo self::paint($text . '{reset}') . PHP_EOL;
    }

    public static function out($text)
    {
        echo self::paint($text) . PHP_EOL;
    }

    /**
     * Ask and get answer.
     *
     * @param string|int $text,
     * @param array $variants Array of variant answers
     * @return string Input text
     */
    public static function ask($text, $variants = ['y', 'N'])
    {
        echo self::line($text . ' {yellow}[' . implode('/', $variants) . ']{reset}: ');
        return trim(fgets(STDIN));
    }

    public static function title($title, $lineChars = '*', $repeatTimes = 7, $type = 'info')
    {
        self::log(str_repeat($lineChars, $repeatTimes) . " {$title} " . str_repeat($lineChars, $repeatTimes), $type);
    }

    /**
     * @param string|array|stdClass $text
     * @param string $type
     * @return void
     */
    public static function log($text, $type = 'info')
    {
        switch ($type) {
            case 'info':
                $color = '{light_cyan}';
                break;

            case 'success':
                $color = '{light_green}';
                break;

            case 'error':
                $color = '{light_red}';
                break;

            case 'warning':
                $color = '{brown}';
                break;

            default:
                $color = '{reset}';
                break;
        }

        if (is_array($text) || is_object($text)) {
            $text = json_encode($text, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        $time = date('d.m.Y H:i:s');

        $backtrace = debug_backtrace();

        $class = end($backtrace)['class'] ?? 'Unknown';
        $class = is_object($class) ? get_class($class) : $class;
        $class = basename(str_replace('\\', '/', $class));

        self::line("[{$time}] [{$class}] {$color}[$type] {$text}");
    }
}
