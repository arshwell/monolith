<?php

use ArshWell\Monolith\Session;
use ArshWell\Monolith\Module;
use ArshWell\Monolith\Layout;
use ArshWell\Monolith\Piece;
use ArshWell\Monolith\Meta;
use ArshWell\Monolith\ENV;
use ArshWell\Monolith\URL;
use ArshWell\Monolith\Web;
use ArshWell\Monolith\DB;

session_start();

require("vendor/autoload.php");

require("vendor/arshwell/monolith/src/ENV.php");

DB::connect('default');
Session::set(ENV::url().ENV::db('conn.default.name'));

// Supervisors are alerted if there are problems.
if (ENV::board('dev') && ENV::supervisor() && $_SERVER['REQUEST_METHOD'] == 'GET') {
    require("vendor/arshwell/monolith/DevTools/checks.php");
}

Web::fetch()::prepare(
    preg_replace('~^'. ENV::root() .'~', '', URL::path()),
    $_SERVER['REQUEST_METHOD'],
    false
);

// Supervisors can use DevPanel and access DevFiles.
if (ENV::supervisor()) {
    // NOTE: We do it here, before Session::memorize(), for not saving DevTools actions in Session.
    require("vendor/arshwell/monolith/DevTools/tools.php");
}

if (!Web::warning(Web::WRNNG_NONE) && $_SERVER['REQUEST_METHOD'] == 'GET') {
    Web::go(Web::key(), Web::params(), Web::language(), Web::page(), $_GET, 301);
    exit;
}

// if we got a valid route
// NOTE: we check here, later, for letting DevTools do their job
if (Web::prepared()) {
    Session::memorize(); // save request in history
}
else {
    http_response_code(404);
    exit;
}

// setting current language (taken from url)
(Web::route()[5])::set(Web::language() ?: (Web::route()[5])::default());

if (Web::isType('AJAX')) {
    if ($_POST['ajax_token'] != Session::token('ajax')) {
        http_response_code(401) ?: http_response_code(403); // session expired
        exit;
    }

    if (ENV::board('dev') == false && !empty($_POST['arsavinel-arshwell-mxdvcwdthflg'])) { // max device width flag
        Session::setDesign($_POST['arsavinel-arshwell-mxdvcwdthflg']);
    }
}

// redirect to HTTP or HTTPS, if needed
if ($_SERVER['REQUEST_METHOD'] == 'GET' && (!isset($_SERVER['HTTP_REFERER']) || Session::isNew())
&& (URL::hasSSL() XOR URL::protocol() == 'https')) {
    header("Location: ". URL::protocol(true) ."://" . URL::get(false), true, 301);
    exit;
}

if ((ENV::class('maintenance'))::isActive()) {
    if (ENV::supervisor()) {
        // supervisor keep their session, so they can acces DevPanel
        Web::force((ENV::class('maintenance'))::route());
    }
    else if ((ENV::class('maintenance'))::isSmart() && !Session::isNew()) {
        // NOTE: Client still can use the app.
    }
    else {
        // HACK: so here are the other 3 situations
        Session::empty(); // for keeping the session new
        Web::force((ENV::class('maintenance'))::route());
    }
}

/* Gates */
    foreach (glob("gates/*.php") as $v) {
        require($v);
    }

unset($v); // NOTE: used by gates/ and ENV.php ↑

/* Backend */
    if (Web::isType('GET') // GET request
    || (Web::isType('AJAX') && !Web::allows('AJAX') && Web::allows('GET'))) { // GET by AJAX
        if (!is_file('outcomes/'. Web::folder() .'/back.module.php')) {
            require('outcomes/'. Web::folder() .'/backend.php');
        }
        else {
            $module = Module::backend(require('outcomes/'. Web::folder() .'/back.module.php'), $_GET);

            if ($module['response']['access'] == false) {
                header("Location: ". $module['response']['redirect'], true, 301);
                exit;
            }
            else if ($module['request'] == 'action/select' && count($module['response']['data']) == 0 && Web::page() > 1) {
                Web::go(Web::key(), Web::params(), Web::language(), Web::page() - 1, $_GET);
                exit;
            }
        }
    }
    else { // Any request that's not GET
        if (!is_file('outcomes/'. Web::folder() .'/back.module.php')) {
            require('outcomes/'. Web::folder() .'.php');
        }
        else {
            echo Module::backend(require('outcomes/'. Web::folder() .'/back.module.php'), $_POST, $_FILES);
        }
        exit;
    }

/* Frontend */
    if (Web::isType('GET')) {
        ob_start();
            echo call_user_func(function ($vars) {
                extract($vars, EXTR_SKIP);

                require('layouts/'. Layout::utils('outcomes/'. Web::folder())['json']['layout'] .'/layout.php');
            }, get_defined_vars());

            ob_start();
                if (!is_file('outcomes/'. Web::folder() .'/front.module.php')) {
                    require('outcomes/'. Web::folder() .'/frontend.php');
                }
                else {
                    echo Module::frontend($module, require('outcomes/'. Web::folder() .'/front.module.php'));
                }

                // Update to date the media links (before media files are selected).
                // NOTE: Also on live, project needs recompiling after some poor update.
                $compiled = call_user_func(function () {
                    $folder = Web::folder();
                    $pieces = Piece::used();

                    return array(
                        'css' => Layout::compileSCSS($folder, $pieces),
                        'js' => array(
                            'header' => Layout::compileJSHeader($folder, $pieces),
                            'footer' => Layout::compileJSFooter($folder, $pieces)
                        )
                    );
                });

                // Supervisors see all resources separately.
                if (ENV::board('dev') && ENV::supervisor()) {
                    $links = Layout::devFiles();

                    $time = substr(str_shuffle("BCDFGHKLMNPQRSTVWXYZ"), 0, 4); // without vowels

                    $ml = array(
                        'urls' => array(
                            'css' => implode('?v='.$time.'" />'.PHP_EOL.'<link type="text/css" rel="stylesheet" href="', $links['css']),
                            'js'  => array(
                                'header' => implode('?v='.$time.'"></script>'.PHP_EOL.'<script src="', $links['js']['header']),
                                'footer' => implode('?v='.$time.'"></script>'.PHP_EOL.'<script src="', $links['js']['footer'])
                            )
                        )
                    );
                }
                else { // Using all resources together.
                    $ml = Layout::mediaLinks();

                    $time = max(
                        getlastmod(), // Last modification of the current page (probably not useful)
                        filemtime($ml['paths']['css']),
                        filemtime($ml['paths']['js']['header']),
                        filemtime($ml['paths']['js']['footer'])
                    );
                }

                $output = str_replace(
                    '[@css@]',
                    '<meta name="csrf-form-token" content="'.Session::token('form').'">'.PHP_EOL.
                    '<meta name="csrf-ajax-token" content="'.Session::token('ajax').'">'.PHP_EOL.PHP_EOL.
                    '<link rel="stylesheet" type="text/css" href="'. ($ml['urls']['css'].'?v='.$time) .'" />',
                    str_replace(
                        '[@js-header@]',
                        '<script src="'. ($ml['urls']['js']['header'].'?v='.$time) .'"></script>',
                        str_replace(
                            '[@js-footer@]',
                            '<script src="'. ($ml['urls']['js']['footer'].'?v='.$time) .'"></script>',
                            str_replace(
                                '[@frontend@]',
                                ob_get_clean(), // outcome
                                ob_get_clean()  // layout
                            )
                        )
                    )
                );
            // ↑ ob_start()
        // ↑ ob_start()

        echo($output); // display

        // Supervisors can see DevPanel (if they write down current version).
        if (ENV::supervisor()) {
            require("vendor/arshwell/monolith/DevTools/tools/panel/button.php"); // NOTE: it uses the $compiled variable
        }

        // Empty outdated forms data.
        Session::unset('form', function (string $key, array $value): bool {
            return !$value['immortal'];
        });
    }
    else if (Web::isType('AJAX')) {
        // Update to date the media links.
        if (ENV::board('dev')) {
            call_user_func(function () {
                $folder = Web::folder();
                $pieces = Piece::used();

                Layout::compileSCSS($folder, $pieces);
                Layout::compileJSHeader($folder, $pieces);
                Layout::compileJSFooter($folder, $pieces);
            });
        }

        ob_start();

            require('outcomes/'. Web::folder() .'/frontend.php');

        echo json_encode(array(
            'metas' => Meta::array($_POST['metas']),
            'html'  => ob_get_clean(),
            'media' => Layout::mediaLinks()
        ));
    }
