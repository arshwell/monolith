<?php

/**
 * @package Arsh/DevTools
 * @author Valentin Arșavin <valentin@iscreambrands.ro>
 */
function _signature (string $site = NULL): ?string {
    $emails = array(
        'valentin@iscreambrands.ro',
        'wallentyn_t@yahoo.com',
        'arsavinwallentyn@gmail.com'
    );
    $text_1 = '[Framework] and [Website'. ($site ? ' - '.$site : '') .']';
    $text_2 = 'developed by ['. implode(' OR ', $emails) .']';

    $len_1 = strlen($text_1);
    $len_2 = strlen($text_2);
    $maxlen = max($len_1, $len_2);

    return (
        '/*****'. str_repeat('*', $maxlen) .'******'
        .PHP_EOL.
        '***   '. str_repeat(' ', $maxlen) .'   ***'
        .PHP_EOL.
        '***   '. $text_1 .str_repeat(' ', $maxlen - $len_1).'   ***'
        .PHP_EOL.
        '***   '. $text_2 .str_repeat(' ', $maxlen - $len_2).'   ***'
        .PHP_EOL.
        '***   '. str_repeat(' ', $maxlen) .'   ***'
        .PHP_EOL.
        '******'. str_repeat('*', $maxlen) .'*****/'
    );
}
