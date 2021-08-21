<?php


namespace App\Translation;

/**
 * Class T
 * This is a dummy class. It serves no purpose other than to mark a string as being translatable.
 * @package App\Translation
 */
class T
{
    /**
     * Call this function on any string to signify that this string is supposed to be translatable.
     * @note This function WILL NOT translate anything; it simply returns the same string it was given. However,
     * wrapping a string in this function will allow ExpandedPhpExtractor to pick up the string when scanning for
     * translatable strings.
     * @param string $s
     * @param string $domain
     * @return string
     */
    public static function __(string $s, string $domain = 'messages'): string { return $s; }
}