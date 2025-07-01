<?php
namespace App\Core;

class Debug
{
    public static function dump($var): void
    {
        echo '<pre>';
        var_dump($var);
        echo '</pre>';
    }

    public static function dumpAndDie($var): void
    {
        self::dump($var);
        die();
    }
}