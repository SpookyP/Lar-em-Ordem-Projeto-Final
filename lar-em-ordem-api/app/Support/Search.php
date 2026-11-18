<?php

namespace App\Support;

final class Search
{
    public static function contains(string $text): string
    {
        return '%' . addcslashes($text, '\\%_') . '%';
    }
}