<?php

namespace Arsavinel\Arshwell\DevTool;

use Arsavinel\Arshwell\Session;
use Arsavinel\Arshwell\URL;

/**
 * Static class for printing debuging and DevTools data in development phase.

 * @package https://github.com/arsavinel/ArshWell
 */
final class DevToolHTML
{

    /**
     * It prints entire HTML layout for debuging or DevPanel.
     */
    static function html(string $text, bool $trusty = true): void
    {
        $rshwll = substr(md5(DevToolData::ArshWellVersion()), 0, 5);
        $url    = URL::get(true, false);

        ob_start(); // for returning all content
        ?>
            <!DOCTYPE html>
            <html lang="ro">

            <head>
                <title>ArshWell <?= ($trusty ? DevToolData::ArshWellVersion() : '') ?></title>

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

                <link rel="stylesheet" type="text/css" href="<?= $url ?>?rshwll=<?= $rshwll ?>&hdr=text/css&fl=design/css/bootstrap/v4.css"></link>
                <link rel="stylesheet" type="text/css" href="<?= $url ?>?rshwll=<?= $rshwll ?>&hdr=text/css&fl=design/css/sindresorhus/github-markdown-css/v5.css"></link>
                <link rel="stylesheet" type="text/css" href="<?= $url ?>?rshwll=<?= $rshwll ?>&hdr=text/css&fl=design/css/sindresorhus/github-markdown-css/v5/dark.css"></link>
            </head>

            <body>
                <?= $text ?>

                <?php
                if ($trusty) { ?>
                    <script type="text/javascript" src="<?= $url ?>?rshwll=<?= $rshwll ?>&hdr=text/javascript&fl=design/js/jquery/v3.js"></script>
                    <script type="text/javascript" src="<?= $url ?>?rshwll=<?= $rshwll ?>&hdr=text/javascript&fl=design/js/floating-ui/v1.js"></script>
                    <script type="text/javascript" src="<?= $url ?>?rshwll=<?= $rshwll ?>&hdr=text/javascript&fl=design/js/bootstrap/v4.js"></script>
                    <script type="text/javascript" src="<?= $url ?>?rshwll=<?= $rshwll ?>&hdr=text/javascript&fl=design/js/chartjs/v3.js"></script>
                    <script type="text/javascript" src="<?= $url ?>?rshwll=<?= $rshwll ?>&hdr=text/javascript&fl=design/js/ArshWell/body/v1.js"></script>
                    <script type="text/javascript" src="<?= $url ?>?rshwll=<?= $rshwll ?>&hdr=text/javascript&fl=design/js/ArshWell/http_build_query/v1.js"></script>
                    <script type="text/javascript" src="<?= $url ?>?rshwll=<?= $rshwll ?>&hdr=text/javascript&fl=design/js/ArshWell/Web/v2.js"></script>
                    <script type="text/javascript" src="<?= $url ?>?rshwll=<?= $rshwll ?>&hdr=text/javascript&fl=design/js/ArshWell/VanillaJS/v1.js"></script>
                    <script type="text/javascript" src="<?= $url ?>?rshwll=<?= $rshwll ?>&hdr=text/javascript&fl=design/js/ArshWell/Form/v2.js"></script>
                <?php } ?>
            </body>
            </html>
        <?php
        echo ob_get_clean();
        exit;
    }

    // gray
    static function code(string $text): string
    {
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
    static function link(string $href, string $text, bool $new_tab = true): string
    {
        return '<a style="color: inherit; white-space: nowrap;" ' . ($new_tab ? 'target="_blank "' : '') . 'href="' . $href . '">' . $text . '</a>';
    }

    // green
    static function var(string $var, bool $const = false): string
    {
        return "<span style='color: #4EA1DF; white-space: nowrap;'>" . ($const ? '$' : '') . $var . "</span>";
    }

    // green
    static function string(string $text, bool $apostrophes = false): string
    {
        return "<span style='color: #2E7D32; white-space: nowrap;'>" .
            (!$apostrophes ? '"' : "'") . $text . (!$apostrophes ? '"' : "'") .
            "</span>";
    }

    // red
    static function int(int $int): string
    {
        return "<span style='color: #DA564A;'>" . $int . "</span>";
    }

    // brown
    static function bool($bool): string
    {
        return "<span style='color: brown;'>" . (is_string($bool) && in_array(strtolower($bool), ['true', 'false']) ? $bool : (is_bool($bool) ? ($bool ? 'true' : 'false') : '')) . "</span>";
    }

    // blue
    static function hug(string $element, string $text): string
    {
        return "<span style='color: #07a;'>" . $element . "</span>(" . $text . ")";
    }

    // blue
    static function array(): string
    {
        $array = ((func_num_args() == 1 && is_array(func_get_arg(0))) ? func_get_arg(0) : func_get_args());

        return "<span style='color: #07a;'>array</span>(<div style='margin-left: 30px;'>" . implode(',<br>', array_map(function ($value) {
            if (is_numeric($value))
                return self::int($value);
            if (is_string($value))
                return self::string($value);
            if (is_bool($value))
                return self::bool($value);
            if (is_array($value))
                return self::array($value);

            return $value;
        }, $array)) . "</div>)";
    }

    // red
    static function error(string $text): string
    {
        return "<div style='background-color: #c0392b; line-height: 23px; color: #fff; font-size: 14px; padding: 0px 6px 1px 6px;'>" . $text . "</div>";
    }

    // green
    static function success(string $text): string
    {
        return "<div style='background-color: #2E7D32; line-height: 23px; color: #fff; font-size: 14px; padding: 0px 6px 1px 6px;'>" . $text . "</div>";
    }

    static function result(): string
    {
        return " <span style='font-size: 10px;'>=></span> ";
    }

    static function hr(): string
    {
        return "<hr style='border: 0; height: 1px;
                            background-image: -webkit-linear-gradient(left, #f0f0f0, #999, #f0f0f0);
                            background-image: -moz-linear-gradient(left, #f0f0f0, #999, #f0f0f0);
                            background-image: -ms-linear-gradient(left, #f0f0f0, #999, #f0f0f0);
                            background-image: -o-linear-gradient(left, #f0f0f0, #999, #f0f0f0);
                '>";
    }

    /**
     * Creates HTML of contributing details.

     * @return string with html
     */
    static function contributing(): string
    {
        ob_start(); // for returning all content
        ?>
            <div class="markdown-body p-4">
                <h1 dir="auto">
                    <a href="https://github.com/arsavinel/ArshWell" target="_blank" aria-hidden="true">
                        Contributing <small>to ArshWell <small>on GitHub project</small></small>
                    </a>
                </h1>

                <p dir="auto">Thank you for considering contributing to the ArshWell framework!</p>

                <ul dir="auto">
                    <li>
                        <p dir="auto">
                            Fork the repo, from
                            <a href="https://github.com/arsavinel/ArshWell" target="_blank" aria-hidden="true">
                                GitHub
                            </a>
                        </p>
                    </li>
                    <li>
                        <p dir="auto">
                            Run, from terminal, in the root of your project: <br>
                            <code>composer require [your-user]/[your-new-fork] --prefer-source</code>
                        </p>
                        <ul dir="auto">
                            <li>In that way, you can modify ArshWell directly inside your vendor's project</li>
                            <li>And after that, just <code>git commit</code> & <code>git push</code> the ArshWell from you vendor</li>
                        </ul>
                    </li>
                    <li>
                        <p dir="auto">Come back to GitHub ArshWell and create a Pull Request</li></p>
                        <ul dir="auto">
                            <li>Explain the problem you've found</li>
                            <li>Present the solution you've implemented.</li>
                        </ul>
                    </li>
                </ul>
            </div>
        <?php
        return ob_get_clean();
    }
}
