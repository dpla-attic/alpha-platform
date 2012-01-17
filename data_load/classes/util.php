<?php

namespace org\librarycloud\data_load\utils;

/** 
* A dumping ground for common data load functions
*/

class util {

    // Taken directly from http://www.php.net/manual/en/function.uniqid.php
    // TODO: Investigate a better uuid solution
    static function uuid() {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
            mt_rand( 0, 0x0fff ) | 0x4000,
            mt_rand( 0, 0x3fff ) | 0x8000,
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ) );
    }

    // Get an ISO8601 formatted date
    // return null if $date_value is empty
    static function format_date($date_value) {
        if (empty($date_value)) {
            return;
        }
        return gmdate('Y-m-d\TH:i:s\Z', strtotime($date_value));
    }

    // Create a Google friendly string (to use in a uri)
    static function link_title($title) {
        $title = preg_replace('/&sbquo;|&rsquo;|&fnof;|&bdquo;|&hellip;|&dagger;|&Dagger;|&circ;|&lsaquo;|&lsquo;|&ldquo;|&rdquo;|&ndash;|&mdash;|&tilde;|&rsaquo;/', '', $title);
        $title = self::ReplaceAccents($title);
        $title_words = explode(' ', strtolower($title));
        $link_title = trim(implode(' ', array_slice($title_words, 0, 6)));
        $link_title = str_replace(' :', '', $link_title);
        $link_title = str_replace('#', '', $link_title);
        $link_title = str_replace(',', '', $link_title);
        $link_title = str_replace('\'', '', $link_title);
        $link_title = str_replace('"', '', $link_title);
        $link_title = str_replace('.', '', $link_title);
        $link_title = preg_replace("/[^a-zA-Z0-9\s]/", "", $link_title);
        $link_title = htmlspecialchars(str_replace(' ', '-', $link_title));
        return $link_title;
    }

    static function ReplaceAccents ($s) {
        $a = array (
            chr(195).chr(167)=>'c', //c with cedilla
        chr(231)=>'c',
            chr(195).chr(166)=>'ae', //a and e next to each other
        chr(230)=>'ae',
            chr(197).chr(147)=>'oe', //o and e next to each other
        chr(195).chr(161)=>'a', //a acute (small slash from bottom left)
        chr(225)=>'a',
            chr(195).chr(169)=>'e', //e acute
        chr(233)=>'e',
            chr(195).chr(173)=>'i', //i acute
        chr(237)=>'i',
            chr(195).chr(179)=>'o', //o acute
        chr(243)=>'o',
            chr(195).chr(186)=>'u', //u acute
        chr(250)=>'u',
            chr(195).chr(160)=>'a', //a grave (small slash from top left)
        chr(224)=>'a',
            chr(195).chr(168)=>'e', //e grave
        chr(232)=>'e',
            chr(195).chr(172)=>'i', //i grave
        chr(236)=>'i',
            chr(195).chr(178)=>'o', //o grave
        chr(242)=>'o',
            chr(195).chr(185)=>'u', //u grave
        chr(249)=>'u',
            chr(195).chr(164)=>'a', //a umlaut (two dots)
        chr(228)=>'a',
            chr(195).chr(171)=>'e', //e umlaut
        chr(235)=>'e',
            chr(195).chr(175)=>'i', //i umlaut
        chr(239)=>'i',
            chr(195).chr(182)=>'o', //o umlaut
        chr(246)=>'o',
            chr(195).chr(188)=>'u', //u umlaut
        chr(252)=>'u',
            chr(195).chr(191)=>'y', //y umlaut
        chr(255)=>'u',
            chr(195).chr(162)=>'a', //a circumflex (a little hat)
        chr(226)=>'a',
            chr(195).chr(170)=>'e', //e circumflex
        chr(234)=>'e',
            chr(195).chr(174)=>'i', //i circumflex
        chr(238)=>'i',
            chr(195).chr(180)=>'o', //o circumflex
        chr(244)=>'o',
            chr(195).chr(187)=>'u', //u circumflex
        chr(251)=>'u',
            chr(195).chr(165)=>'a', //a with a small ring on top
        chr(229)=>'a',
            chr(101).chr(0)=>'e', //e
        chr(105).chr(0)=>'i', //i
        chr(195).chr(184)=>'o', //o with a slash through it
        chr(248)=>'o',
            chr(117).chr(0)=>'u', //u
        );
        return strtr ($s, $a);
    }

}

?>