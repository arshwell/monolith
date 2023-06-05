<?php

namespace Arshwell\Monolith\DevTool;

use Arshwell\Monolith\Session;
use Arshwell\Monolith\URL;

/**
 * Static class for printing debuging and DevTools data in development phase.

 * @package https://github.com/arshwell/monolith
 */
final class DevToolHTML
{

    /**
     * It prints entire HTML layout for debuging or DevPanel.
     */
    static function html(string $text, bool $trusty = true): void
    {
        $rshwll = substr(md5(DevToolData::ArshwellVersion()), 0, 5);
        $url    = URL::get(true, false);

        ob_start(); // for returning all content
        ?>
            <!DOCTYPE html>
            <html lang="en">

            <head>
                <title>Arshwell <?= ($trusty ? DevToolData::ArshwellVersion() : '') ?></title>

                <meta http-equiv="content-type" content="text/html;charset=utf-8" />
                <meta http-equiv="X-UA-Compatible" content="IE=edge">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <meta name="robots" content="noindex, nofollow">

                <?php
                if ($trusty) { ?>
                    <meta name="csrf-form-token" content="<?= Session::token('form') ?>">
                    <meta name="csrf-ajax-token" content="<?= Session::token('ajax') ?>">

                    <link href="<?= $url ?>?rshwll=<?= $rshwll ?>&hdr=image/png&rsrc=images/DevPanel/favicon.png" rel="shortcut icon" type="image/*" />
                <?php } ?>

                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.2/css/bootstrap.min.css" integrity="sha512-rt/SrQ4UNIaGfDyEXZtNcyWvQeOq0QLygHluFQcSjaGB04IxWhal71tKuzP6K8eYXYB6vJV4pHkXcmFGGQ1/0w==" crossorigin="anonymous" referrerpolicy="no-referrer" />
                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/github-markdown-css/5.2.0/github-markdown.min.css" integrity="sha512-Ya9H+OPj8NgcQk34nCrbehaA0atbzGdZCI2uCbqVRELgnlrh8vQ2INMnkadVMSniC54HChLIh5htabVuKJww8g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/github-markdown-css/5.2.0/github-markdown-dark.min.css" integrity="sha512-Mo2QuokS9Y0JOuzVLUh3o9A07RqSXcpc2KC9LXxOwfaBgPt8ZHRiDfGQ2+tZw7xIno+ViWipTNLg1StC6TmwMA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
            </head>

            <body>
                <?= $text ?>

                <?php
                if ($trusty) { ?>
                    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js" integrity="sha512-3gJwYpMe3QewGELv8k/BX9vcqhryRdzRMxVfq6ngyWXwo03GFEzjsUm8Q7RZcHPHksttq7/GFoxjCVUjkjvPdw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
                    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.1/umd/popper.min.js" integrity="sha512-ubuT8Z88WxezgSqf3RLuNi5lmjstiJcyezx34yIU2gAHonIi27Na7atqzUZCOoY4CExaoFumzOsFQ2Ch+I/HCw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
                    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.2/js/bootstrap.min.js" integrity="sha512-7rusk8kGPFynZWu26OKbTeI+QPoYchtxsmPeBqkHIEXJxeun4yJ4ISYe7C6sz9wdxeE1Gk3VxsIWgCZTc+vX3g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
                    <script type="text/javascript" src="<?= $url ?>?rshwll=<?= $rshwll ?>&hdr=text/javascript&rsrc=js/body/v1.js"></script>
                    <script type="text/javascript" src="<?= $url ?>?rshwll=<?= $rshwll ?>&hdr=text/javascript&rsrc=js/http_build_query/v1.js"></script>
                    <script type="text/javascript" src="<?= $url ?>?rshwll=<?= $rshwll ?>&hdr=text/javascript&rsrc=js/Web/v2.js"></script>
                    <script type="text/javascript" src="<?= $url ?>?rshwll=<?= $rshwll ?>&hdr=text/javascript&rsrc=js/VanillaJS/v1.js"></script>
                    <script type="text/javascript" src="<?= $url ?>?rshwll=<?= $rshwll ?>&hdr=text/javascript&rsrc=js/Form/v2.js"></script>
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

    /**
     * @param string (because some numbers are too long)
     *
     * @return string (red text)
     */
    static function int(string $int): string
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
                    <a href="https://github.com/arshwell/monolith" target="_blank" aria-hidden="true">
                        Contributing <small>to Arshwell <small>on GitHub project</small></small>
                    </a>
                </h1>

                <p dir="auto">Thank you for considering contributing to the Arshwell framework!</p>

                <ul dir="auto">
                    <li>
                        <p dir="auto">
                            Fork the repo, from
                            <a href="https://github.com/arshwell/monolith" target="_blank" aria-hidden="true">
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
                            <li>In that way, you can modify Arshwell directly inside your vendor's project</li>
                            <li>And after that, just <code>git commit</code> & <code>git push</code> the Arshwell from your vendor</li>
                        </ul>
                    </li>
                    <li>
                        <p dir="auto">Come back to GitHub Arshwell and create a Pull Request</li></p>
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
