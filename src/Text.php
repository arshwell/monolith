<?php

namespace ArshWell\Monolith;

/**
 * Class for backend programming which has routine functions.

 * @package https://github.com/arshwell/monolith
*/
final class Text {
    static function removeAllTags (string $string, string $allow = NULL) {
        // TODO: After setting PHP 7.4+, make $allow an array.

        return trim(strip_tags(html_entity_decode($string), $allow));
    }

    static function removeTags (string $string, array $tags) {
        $string = html_entity_decode($string);

        foreach ($tags as $tag) {
            $string = preg_replace('/<'. $tag .'\b[^>]*>(.*?)<\/'. $tag .'>/is', '', $string);
        }
        return $string;
    }

    static function slug (string $string): string {
        return trim(preg_replace(
            '/[\s-]+/', '-',
            trim((str_replace(
                str_split(".,:;'\"\\@„”`#^/\+\/*!?<>|[](){}=%"), ' ',
                trim(str_replace(
                    ['ă','î','â','ș','ț'], ['a','i','a','s','t'],
                    preg_replace('/[^ăîâșț[:print:]]/', '', mb_strtolower(self::removeAllTags($string)))
                ))
            )))
        ));
    }

    static function chars (string $string, int $limit, string $break = ' ', string $pad = '...'): string {
        $string = preg_replace('/\s+/', ' ', self::removeAllTags($string));

        // return with no change if string is shorter than $limit
        if (strlen($string) <= $limit) {
            return $string;
        }

        // is $break present between $limit and the end of the string?
        if (($breakpoint = strpos($string, $break, $limit)) !== false && $breakpoint < strlen($string) - 1) {
            $string = rtrim(substr($string, 0, $breakpoint), ':,;') . $pad;
        }

        return $string;
    }

    static function words (string $sentence, int $limit): string {
        return implode('',
            array_slice(
                preg_split('/([\s,\.;\?\!]+)/', $sentence, $limit*2+1, PREG_SPLIT_DELIM_CAPTURE),
                0,
                $limit*2 - 1
            )
        );
    }

    // static function chars (string $text, int $limit, bool $reversed = false);
    // static function words (string $text, int $limit, bool $reversed = false);
    // static function sentences (string $text, int $limit, bool $reversed = false);

    static function commonStartChars (string $s1, string $s2): string {
        $min = min(strlen($s1), strlen($s2));
        for ($i = 0; $i < $min; $i++)
            if ($s2[$i] != $s1[$i])
                break;

        return substr($s1, 0, $i);
    }
}
