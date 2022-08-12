<?php

use Arsavinel\Arshwell\Session;
use Arsavinel\Arshwell\Git;
use Arsavinel\Arshwell\URL;

/**
 * Functions for DevTools which imitate the php syntax.

 * @package https://github.com/arsavinel/ArshWell
 */
function _html (string $text, bool $trusty = true): void {
    $rshwll = substr(md5(Git::tag()), 0, 5);
    $url    = URL::get(true, false);

    ob_start(); // for returning all content
    ?>
        <!DOCTYPE html>
        <html lang="ro">
            <head>
                <title>ArshWell <?= ($trusty ? Git::tag() : '') ?></title>

                <meta http-equiv="content-type" content="text/html;charset=utf-8" />
                <meta http-equiv="X-UA-Compatible" content="IE=edge">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <meta name="robots" content="noindex, nofollow">

                <?php
                if ($trusty) { ?>
                    <meta name="csrf-form-token" content="<?= Session::token('form') ?>">
                    <meta name="csrf-ajax-token" content="<?= Session::token('ajax') ?>">

                    <link href="<?= $url ?>?rshwll=<?= $rshwll ?>&hdr=image/png&fl=images/favicon.png" rel="shortcut icon" type="image/*" />
                <?php } ?>

                <link rel="stylesheet" type="text/css" href="<?= $url ?>?rshwll=<?= $rshwll ?>&hdr=text/css&fl=design/css/bootstrap-v4.5.css"></link>
            </head>
            <body>
                <?= $text ?>

                <?php
                if ($trusty) { ?>
                    <script type="text/javascript" src="<?= $url ?>?rshwll=<?= $rshwll ?>&hdr=text/javascript&fl=design/js/jquery-v3.5.js"></script>
                    <script type="text/javascript" src="<?= $url ?>?rshwll=<?= $rshwll ?>&hdr=text/javascript&fl=design/js/popper.js"></script>
                    <script type="text/javascript" src="<?= $url ?>?rshwll=<?= $rshwll ?>&hdr=text/javascript&fl=design/js/bootstrap-v4.5.js"></script>
                    <script type="text/javascript" src="<?= $url ?>?rshwll=<?= $rshwll ?>&hdr=text/javascript&fl=design/js/chart.js"></script>
                    <script type="text/javascript" src="<?= $url ?>?rshwll=<?= $rshwll ?>&hdr=text/javascript&fl=design/js/custom/body.js"></script>
                    <script type="text/javascript" src="<?= $url ?>?rshwll=<?= $rshwll ?>&hdr=text/javascript&fl=design/js/custom/http_build_query.js"></script>
                    <script type="text/javascript" src="<?= $url ?>?rshwll=<?= $rshwll ?>&hdr=text/javascript&fl=design/js/custom/Web.js"></script>
                    <script type="text/javascript" src="<?= $url ?>?rshwll=<?= $rshwll ?>&hdr=text/javascript&fl=design/js/custom/VanillaJS.js"></script>
                    <script type="text/javascript" src="<?= $url ?>?rshwll=<?= $rshwll ?>&hdr=text/javascript&fl=design/js/custom/Form.js"></script>
                <?php } ?>
            </body>
        </html>
    <?php
    echo ob_get_clean();
    exit;
}

// gray
function _code (string $text): string {
    return "<div style='
                    background: linear-gradient(90deg, #eee 8px, transparent 1%) center,
                                linear-gradient(#eee 8px, transparent 1%) center,
                                #fff;
                    background-size: 10px 10px; line-height: 23px; font-size: 14px; border: 1px solid #999;
                    padding: 4px 6px 4px 6px; color: #555;'>"
                . $text .
          "</div>";
}

// link
function _link (string $href, string $text, bool $new_tab = true): string {
    return '<a style="color: inherit; white-space: nowrap;" '. ($new_tab ? 'target="_blank "' : '') .'href="'. $href .'">'. $text .'</a>';
}

// green
function _var (string $var, bool $const = false): string {
    return "<span style='color: #4EA1DF; white-space: nowrap;'>". ($const ? '$' : '') . $var ."</span>";
}

// green
function _string (string $text, bool $apostrophes = false): string {
    return "<span style='color: #2E7D32; white-space: nowrap;'>".
        (!$apostrophes ? '"' : "'") . $text . (!$apostrophes ? '"' : "'").
    "</span>";
}

// red
function _int (int $int): string {
    return "<span style='color: #DA564A;'>". $int ."</span>";
}

// brown
function _bool ($bool): string {
    return "<span style='color: brown;'>". (is_string($bool) && in_array(strtolower($bool), ['true', 'false']) ? $bool : (is_bool($bool) ? ($bool ? 'true' : 'false') : '')) ."</span>";
}

// blue
function _hug (string $element, string $text): string {
    return "<span style='color: #07a;'>". $element ."</span>(". $text .")";
}

// blue
function _array (): string {
    $array = ((func_num_args() == 1 && is_array(func_get_arg(0))) ? func_get_arg(0) : func_get_args());

    return "<span style='color: #07a;'>array</span>(<div style='margin-left: 30px;'>". implode(',<br>', array_map(function ($value) {
        if (is_numeric($value))
            return _int($value);
        if (is_string($value))
            return _string($value);
        if (is_bool($value))
            return _bool($value);
        if (is_array($value))
            return _array($value);

        return $value;
    }, $array)) ."</div>)";
}

// red
function _error (string $text): string {
    return "<div style='background-color: #c0392b; line-height: 23px; color: #fff; font-size: 14px; padding: 0px 6px 1px 6px;'>". $text ."</div>";
}

// green
function _success (string $text): string {
    return "<div style='background-color: #2E7D32; line-height: 23px; color: #fff; font-size: 14px; padding: 0px 6px 1px 6px;'>". $text ."</div>";
}

function _result (): string {
    return " <span style='font-size: 10px;'>=></span> ";
}

function _hr (): string {
    return "<hr style='border: 0; height: 1px;
                        background-image: -webkit-linear-gradient(left, #f0f0f0, #999, #f0f0f0);
                        background-image: -moz-linear-gradient(left, #f0f0f0, #999, #f0f0f0);
                        background-image: -ms-linear-gradient(left, #f0f0f0, #999, #f0f0f0);
                        background-image: -o-linear-gradient(left, #f0f0f0, #999, #f0f0f0);
            '>";
}
