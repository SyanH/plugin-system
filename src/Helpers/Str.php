<?php

namespace Syan\PluginSystem\Helpers;

/**
 * Class Str
 * @package Syan\PluginSystem\Helpers
 */
class Str
{
    /**
     * Check if string starts with
     *
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function startsWith(string $haystack, string $needle) : bool
    {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }

    /**
     * Check if string ends with
     *
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function endsWith(string $haystack, string $needle) : bool
    {
        if (!strlen($needle)) return true;
        return substr($haystack, -strlen($needle)) === $needle;
    }

    /**
     * Convert to array and get last.
     *
     * @param string $string
     * @param string $separator
     * @return string
     */
    public static function afterLast(string $string, string $separator = '.'): string
    {
        $exploded = explode($separator, $string);
        return $exploded[count($exploded) - 1];
    }

    /**
     * Explode string on upper case characters
     *
     * @param string $string
     * @return array
     */
    public static function splitAtUpperCase(string $string): array
    {
        return preg_split('/(?=[A-Z])/', $string, -1, PREG_SPLIT_NO_EMPTY);
    }
}
