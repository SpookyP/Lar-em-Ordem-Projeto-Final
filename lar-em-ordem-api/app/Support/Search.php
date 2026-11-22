<?php

namespace App\Support;

final class Search
{
    /**
     * LIKE pattern for text typed by a user, with %, _ and \ escaped so they match literally.
     */
    public static function contains(string $text): string
    {
        return '%' . addcslashes($text, '\\%_') . '%';
    }
}