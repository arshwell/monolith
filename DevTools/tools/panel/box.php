<?php

use Arsh\Core\Table\TableValidation;
use Arsh\Core\Session;
use Arsh\Core\Layout;
use Arsh\Core\Folder;
use Arsh\Core\Func;
use Arsh\Core\File;
use Arsh\Core\ENV;
use Arsh\Core\URL;
use Arsh\Core\Web;
use Arsh\Core\Git;

$info = TableValidation::run(
    array_merge(
        array('form_token' => Session::token('form')),
        (array)@unserialize(urldecode($_GET['data'] ?? array())) // using @ because could be invalid user input
    ),
    array(
        'time' => array(
            "required|int|minLength:10|maxLength:11"
        ),
        'new' => array(
            "is_bool"
        ),
        'PHP' => array(
            "required|float",
            function ($value) {
                return (int)$value;
            },
            "min:1|maxLength:10",
            function ($value) {
                return Func::readableTime($value);
            }
        ),
        'path' => array(
            "required|is_string"
        ),
        'route' => array(
            "required|is_string",
            function ($key, $value) {
                if (!Web::exists($value)) {
                    return true;
                }
            }
        ),
        'language' => array(
            "optional|is_string"
        ),
        'pieces' => array(
            "array",
            array(
                "required|is_string"
            )
        ),
        'compiled' => array(
            "optional|array",
            'css' => array(
                "is_bool",
                function ($value) {
                    return $value;
                }
            ),
            'js' => array(
                "required|array",
                'header' => array(
                    "is_bool",
                    function ($value) {
                        return $value;
                    }
                ),
                'footer' => array(
                    "is_bool",
                    function ($value) {
                        return $value;
                    }
                ),
            )
        )
    ),
    false // don't add message errors
);

if ($info->valid() == false) {
    header("Location: ". URL::get(true, false));
    exit;
}

// $sessions = Session::all(false, true); // without current session
$sessions = Session::all(true, true); // TODO: Delete it at the end

$max_attempts = array(
    'recompile-css-js' => call_user_func(function () {
        $files = File::tree(ENV::design('css'));

        $route_counter = 0;
        $max_route_files = 0;

        array_walk_recursive($files, function ($file, $index, &$route_counter) use (&$max_route_files) {
            if (is_numeric($index)) {
                $route_counter++;
            }
            if ($route_counter > $max_route_files) {
                $max_route_files = $route_counter;
            }
        }, $route_counter);

        return ceil((count(Web::routes('GET')) * ($max_route_files * 4)) / 250);
    })
);

ob_start(); // for adding all content in _html() function
?>
<style type="text/css">
    html,
    html > body {
        overflow-x: hidden;
        overflow-y: hidden;
        height: 100vh;
        max-height: 100vh;
    }
    html body {
        font-size: smaller;
    }
    html body > .card {
        height: 100vh;
    }
    .nav-link,
    .nav-link:focus,
    .btn,
    .btn:focus {
        outline: 0 !important;
        -webkit-box-shadow: none !important;
        -moz-box-shadow:    none !important;
        box-shadow:         none !important;
    }
    body .card .card-body .tab-content.scrollable > .tab-pane {
        overflow-x: auto;
        overflow-y: auto;
        height: calc(100vh - 49px - 20px - 20px - 49px);
        padding-right: 10px;
    }
        body .card .card-body pre {
            border-radius: 5px;
        }
        body .card .card-body pre::-webkit-scrollbar,
        body .card .card-body .tab-content.scrollable .tab-pane::-webkit-scrollbar {
            width: 8px;
            height: 8px;
            background-color: transparent;
        }
        body .card .card-body pre::-webkit-scrollbar-thumb,
        body .card .card-body .tab-content.scrollable .tab-pane::-webkit-scrollbar-thumb {
            border-radius: 10px;
            -webkit-box-shadow: inset 0 0 6px rgba(0, 0, 0, 0.3);
            background-color: #555;
        }
        body .card .card-body pre::-webkit-scrollbar-track,
        body .card .card-body .tab-content.scrollable .tab-pane::-webkit-scrollbar-track {
            -webkit-box-shadow: inset 0 0 6px rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            background-color: transparent;
        }
        body .card .card-body pre::-webkit-scrollbar-button,
        body .card .card-body .tab-content.scrollable .tab-pane::-webkit-scrollbar-button {
            width: 0;
            height: 0;
            display: none;
        }
        body .card .card-body pre::-webkit-scrollbar-corner,
        body .card .card-body .tab-content.scrollable .tab-pane::-webkit-scrollbar-corner {
            background-color: transparent;
        }
            body .card .card-body .tab-content.scrollable > .tab-pane > .tab-content {
                /* display: inline-block; /* for correct horizontal scrollbar */ */
            }

    body .card .card-body .nav.nav-pills .nav-link {
        margin-bottom: 5px;
    }
    body .card .card-body .tab-content .tab-pane table th.va-top {
        vertical-align: top;
    }
    body .card .card-body .tab-content .tab-pane table th {
        vertical-align: middle;
    }
    .nav-tabs .nav-item .nav-link:not(.active) {
        color: #f8f9fa !important; /* white */
    }
    .list-group .list-group-item {
        background: transparent;
    }

    .form-check-label {
        padding-top: 1.5px;
    }

    .btn.progress-bar-striped.progress-bar-animated.disabled,
    .btn.progress-bar-striped.progress-bar-animated:disabled {
        opacity: .90;
    }

    #actions-build .collapse table {
        display: inline;
    }

    label[for] {
        cursor: pointer;
    }

    .nowrap {
        white-space: nowrap;
    }
    .break-word {
        word-break: break-word;
    }
    .strike-oblique {
        position: relative;
    }
    .strike-oblique:before {
        position: absolute;
        content: "";
        left: 0;
        top: 50%;
        right: 0;
        border-top: 1px solid;
        border-color: red;
        -webkit-transform: rotate(-20deg);
        -moz-transform: rotate(-20deg);
        -ms-transform: rotate(-20deg);
        -o-transform: rotate(-20deg);
        transform: rotate(-20deg);
    }
    .strike-oblique.strike-oblique-10g:before {
        -webkit-transform: rotate(-10deg);
        -moz-transform: rotate(-10deg);
        -ms-transform: rotate(-10deg);
        -o-transform: rotate(-10deg);
        transform: rotate(-10deg);
    }
    input[type="radio"]:hover {
        cursor: pointer;
    }
    textarea {
        height: 38px;
        min-height: 38px;
    }
    a.td-none {
        text-decoration: none;
    }
    a:not([class]) {
        color: white;
    }
    a:not([class]):hover {
        color: #17a2b8; /* blue - .text-info */
    }

    .card .card-footer {
        height: 44px;
        padding-top: 0px;
        padding-bottom: 0px;
    }

    .tooltip-inner {
        max-width: 100% !important;
    }
    .tooltip.bs-tooltip-top    { margin-top: -5px; }
    .tooltip.bs-tooltip-right  { margin-left: 5px; }
    .tooltip.bs-tooltip-bottom { margin-top: 5px; }
    .tooltip.bs-tooltip-left   { margin-left: -5px; }
</style>

<div class="card bg-dark rounded-0 text-light">
    <div class="card-header">
        <div class="row">
            <div class="col-6">
                <i style="text-shadow: 0px 0px 1px #000;" class="pr-1" data-toggle="tooltip" data-placement="right" data-title="Released on 30 August 2019">
                    <span class="text-success">Arsh</span><span class="text-warning">Well</span>
                    <?php
                    if (Session::panel('active') && Git::tag()) { ?>
                        <span class="text-danger"><?= Git::tag() ?></span>
                    <?php } ?>
                </i>
            </div>
            <div class="col-6 text-right">
                <a data-href="<?= URL::get() ?>" class="td-none" target="_blank">
                    <span class="text-success">Dev</span><span class="text-warning">Panel</span>
                </a>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <?php
            if (Session::panel('active')) { // include tabs ?>
                <!-- pills -->
                <div class="d-none d-sm-block col-sm-3">
                    <div class="nav flex-column nav-pills" aria-orientation="vertical">
                        <a href="#info" class="nav-link btn-dark <?= (in_array(Session::panel('box.tab'), [NULL, 'info']) ? 'active show' : '') ?>" data-toggle="pill">
                            Info
                        </a>
                        <a href="#resources" class="nav-link btn-dark <?= (Session::panel('box.tab') == 'resources' ? 'active show' : '') ?>" data-toggle="pill">
                            Resources
                        </a>
                        <a href="#actions" class="nav-link btn-dark <?= (Session::panel('box.tab') == 'actions' ? 'active show' : '') ?>" data-toggle="pill">
                            Actions
                        </a>
                        <a href="#warnings" class="nav-link btn-dark d-flex justify-content-between align-items-center <?= (Session::panel('box.tab') == 'warnings' ? 'active show' : '') ?>" data-toggle="pill">
                            Warnings

                            <?php
                            $warnings = array(
                                'errors' => call_user_func(function (): array {
                                    $errors = File::rFolder('errors', array('log'));

                                    if (is_file('error_log')) {
                                        $errors[] = 'error_log';
                                    }

                                    return $errors;
                                }),
                                'forbidden_files' => call_user_func(function (): array {
                                    $forbidden_files = array();

                                    foreach (File::rFolder('caches') as $file) {
                                        if (!in_array(File::mimeType($file), ['text/plain', 'inode/x-empty'])) { // json OR empty file
                                            $forbidden_files[] = $file;
                                        }
                                    }
                                    foreach (array('App','errors','forks','gates','layouts','mails','outcomes','pieces') as $folder) {
                                        foreach (File::rFolder($folder, [NULL]) as $file) {
                                            if (basename($file) == '.htaccess') {
                                                $forbidden_files[] = $file;
                                            }
                                        }
                                    }
                                    foreach (File::rFolder('uploads') as $file) {
                                        if (!in_array($file, ['uploads/.htaccess', ENV::design().'.htaccess'])
                                        && (in_array(basename($file), ['.htaccess', '.htpasswd'])
                                        || in_array(File::extension($file), ['php', 'phtml'])
                                        || in_array(File::mimeType($file), [NULL, 'text/x-php']))) {
                                            $forbidden_files[] = $file;
                                        }
                                    }

                                    return $forbidden_files;
                                }),
                                'wrong_place_files' => call_user_func(function (): array {
                                    $wrong_place_files = array();

                                    foreach (File::rFolder('App') as $file) {
                                        if (strpos($file, 'App/Core/') !== 0 && File::extension($file) != 'php') { // App/Core is an exception
                                            $wrong_place_files[] = $file;
                                        }
                                    }
                                    foreach (File::rFolder('crons') as $file) {
                                        if (File::extension($file) != 'php' && $file != 'crons/.htaccess') {
                                            $wrong_place_files[] = $file;
                                        }
                                    }
                                    foreach (File::rFolder('errors') as $file) {
                                        if (File::extension($file) != 'log') {
                                            $wrong_place_files[] = $file;
                                        }
                                    }
                                    foreach (File::rFolder('forks') as $file) {
                                        if (File::extension($file) != 'json') {
                                            $wrong_place_files[] = $file;
                                        }
                                    }
                                    foreach (File::rFolder('gates') as $file) {
                                        if (File::extension($file) != 'php') {
                                            $wrong_place_files[] = $file;
                                        }
                                    }
                                    foreach (array('layouts','mails','outcomes','pieces') as $folder) {
                                        foreach (File::rFolder($folder) as $file) {
                                            if (!in_array(File::extension($file), ['php', 'json', 'js', 'scss'])) {
                                                $wrong_place_files[] = $file;
                                            }
                                        }
                                    }

                                    return $wrong_place_files;
                                })
                            );
                            ?>
                            <span class="rounded px-1 d-table text-center float-right btn-<?= ((count($warnings['errors']) || count($warnings['forbidden_files'])) ? 'danger' : (count($warnings['wrong_place_files']) ? 'warning' : 'primary')) ?>">
                                <?= (count($warnings, COUNT_RECURSIVE) - 3) ?>
                            </span>
                        </a>
                        <a href="#maintenance" class="nav-link btn-dark <?= (Session::panel('box.tab') == 'maintenance' ? 'active show' : '') ?>" data-toggle="pill">
                            <div class="d-flex align-items-center">
                                Maintenance
                                <?php
                                if (ENV::maintenance('active')) { ?>
                                    <div class="spinner-grow spinner-grow-sm ml-auto <?= (ENV::maintenance('smart') ? 'text-success' : 'text-danger') ?> float-right" aria-hidden="true"></div>
                                <?php } ?>
                            </div>
                        </a>
                        <a href="#history" class="nav-link btn-dark <?= (Session::panel('box.tab') == 'history' ? 'active show' : '') ?>" data-toggle="pill">
                            History
                        </a>
                        <a href="#process" class="nav-link btn-dark <?= (Session::panel('box.tab') == 'process' ? 'active show' : '') ?>" data-toggle="pill">
                            Process
                        </a>
                    </div>
                </div>

                <!-- tab contents -->
                <div class="d-none d-sm-block col-sm-9">
                    <div class="tab-content scrollable">
                        <!-- Info -->
                        <div id="info" class="text-light tab-pane fade <?= (in_array(Session::panel('box.tab'), [NULL, 'info']) ? 'active show' : '') ?>">
                            <!-- tabs -->
                            <ul class="nav nav-tabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link <?= (in_array(Session::panel('box.tab.info'), [NULL, 'route']) ? 'active' : '') ?>" data-toggle="tab" href="#info-route">
                                        About this route
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?= (Session::panel('box.tab.info') == 'site' ? 'active' : '') ?>" data-toggle="tab" href="#info-site">
                                        About site
                                    </a>
                                </li>
                            </ul>

                            <!-- Info - tab contents -->
                            <div class="tab-content">
                                <!-- resources route -->
                                <div id="info-route"
                                class="tab-pane fade py-2 <?= (in_array(Session::panel('box.tab.info'), [NULL, 'route']) ? 'show active' : '') ?>">
                                    <span class="text-muted mb-2 instance-of-panel"></span> <!-- the link we came from -->

                                    <table class="table table-bordered table-dark">
                                        <?php
                                        if ($info->value('new')) { ?>
                                            <tr>
                                                <th>Session</th>
                                                <td>NEW</td>
                                            </tr>
                                        <?php } ?>
                                        <tr>
                                            <th>PHP</th>
                                            <td><?= $info->value('PHP') ?></td>
                                        </tr>
                                        <tr>
                                            <th>Route</th>
                                            <td><?= $info->value('route') ?></td>
                                        </tr>
                                        <tr>
                                            <th rowspan="2">URL</th>
                                            <?php
                                            if (rtrim(Web::pattern($info->value('route'), $info->value('language')), '/') != trim($info->value('path'), '/')) { ?>
                                                <td><?= Web::pattern($info->value('route'), $info->value('language')) ?></td>
                                            <?php } ?>
                                        </tr>
                                        <tr>
                                            <td><?= $info->value('path') ?></td>
                                        </tr>
                                        <tr>
                                            <th>Folder</th>
                                            <td><?= Web::folder($info->value('route')) ?></td>
                                        </tr>
                                        <?php
                                        if ($info->value('compiled')['css'] || $info->value('compiled')['js']['header'] || $info->value('compiled')['js']['footer']) { ?>
                                            <tr>
                                                <th>Compiled</th>
                                                <td>
                                                    <table class="table table-bordered table-dark m-0">
                                                        <tr>
                                                            <?php
                                                            if ($info->value('compiled')['css']) { ?>
                                                                <td>CSS</td>
                                                            <?php } ?>
                                                            <?php
                                                            if ($info->value('compiled')['js']['header']) { ?>
                                                                <td>JS header</td>
                                                            <?php } ?>
                                                            <?php
                                                            if ($info->value('compiled')['js']['footer']) { ?>
                                                                <td>JS footer</td>
                                                            <?php } ?>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </table>
                                </div>

                                <!-- resources site -->
                                <div id="info-site"
                                class="tab-pane fade py-2 <?= (Session::panel('box.tab.info') == 'site' ? 'show active' : '') ?>">
                                    <table class="table table-bordered table-dark">
                                        <tr>
                                            <th>IP</th>
                                            <td>
                                                <?= ENV::clientIP() ?>
                                                <span data-toggle="tooltip" data-placement="top" title="Supervisor key from env.json">
                                                    (<?= array_search(ENV::clientIP(), ENV::board('supervisors')) ?>)
                                                </span>
                                            </td>
                                        </tr>
                                        <tr data-toggle="collapse" href="#routes-count-all,#routes-count-request" role="button">
                                            <th class="va-top" data-toggle="tooltip" data-placement="left" title="Toggle requests">
                                                Routes
                                            </th>
                                            <td>
                                                <div class="collapse show fade" id="routes-count-all">
                                                    <?= count(Web::routes()) ?>
                                                </div>
                                                <table class="table table-bordered table-dark m-0 collapse fade" id="routes-count-request">
                                                    <tr><td colspan="2" class="border-0 p-0">
                                                        <sup class="text-muted">They could be duplicated. Because a route can accept more requests.</sup>
                                                    </td></tr>
                                                    <?php
                                                    foreach (array_unique(call_user_func_array('array_merge', array_column(Web::routes(), 1))) as $request) { ?>
                                                        <tr>
                                                            <th><?= $request ?></th>
                                                            <td><?= count(Web::routes($request)) ?></td>
                                                        </tr>
                                                    <?php } ?>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Resources -->
                        <div id="resources" class="text-light tab-pane fade <?= (Session::panel('box.tab') == 'resources' ? 'active show' : '') ?>">
                            <!-- tabs -->
                            <ul class="nav nav-tabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link <?= (in_array(Session::panel('box.tab.resources'), [NULL, 'route']) ? 'active' : '') ?>" data-toggle="tab" href="#resources-route">
                                        Links for this route
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?= (Session::panel('box.tab.resources') == 'site' ? 'active' : '') ?>" data-toggle="tab" href="#resources-site">
                                        Used by site
                                    </a>
                                </li>
                            </ul>

                            <!-- Resources - tab contents -->
                            <div class="tab-content">
                                <!-- resources route -->
                                <div id="resources-route"
                                class="tab-pane fade py-2 <?= (in_array(Session::panel('box.tab.resources'), [NULL, 'route']) ? 'show active' : '') ?>">
                                    <span class="text-muted mb-2 instance-of-panel"></span> <!-- the link we came from -->

                                    <?php
                                    $links = array(
                                        'css'   => array(
                                            'web'   => Layout::mediaSCSS(Web::folder($info->value('route')), $info->value('pieces'), true)['files'],
                                            'mails' => Layout::mediaMailSCSS(Web::folder($info->value('route')), $info->value('pieces'), true)['files']
                                        ),
                                        'js'    => array(
                                            'header' => Layout::mediaJSHeader(Web::folder($info->value('route')), $info->value('pieces'))['files'],
                                            'footer' => Layout::mediaJSFooter(Web::folder($info->value('route')), $info->value('pieces'))['files']
                                        )
                                    );

                                    array_unshift($links['js']['header'], array(
                                        'name' => 'dynamic/'. Web::folder($info->value('route')) .'/web.js'
                                    ));

                                    $time   = substr(str_shuffle("BCDFGHKLMNPQRSTVWXYZ"), 0, 4);
                                    $asset  = ENV::root().'/uploads/design/';

                                    $mediaLinks = Layout::mediaLinks($info->value('route'), $info->value('pieces')); ?>

                                    <div class="row">
                                        <div class="col-12 col-lg-6">
                                            <div class="card bg-dark mb-2">
                                                <div class="card-header py-2">
                                                    <a href="<?= $mediaLinks['urls']['css'] ?>" target="_blank" data-toggle="tooltip" data-placement="top" title="<?= $mediaLinks['paths']['css'] ?>">
                                                        CSS
                                                    </a>
                                                </div>
                                                <div class="card-body py-1">
                                                    <?php
                                                    if ($links['css']['web']) {
                                                        foreach ($links['css']['web'] as $file) { ?>
                                                            <a href="<?= $asset.'dev/'.$file['name'] ?>?v=<?= $time ?>" target="_blank">
                                                                <?= $file['name'] ?>
                                                            </a><br>
                                                        <?php }
                                                    } ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-lg-6">
                                            <div class="card bg-dark mb-2">
                                                <div class="card-header py-2">
                                                    CSS Mails
                                                </div>
                                                <div class="card-body py-1">
                                                    <?php
                                                    if ($links['css']['mails']) {
                                                        foreach ($links['css']['mails'] as $file) { ?>
                                                            <a href="<?= $asset.'dev/'.$file['name'] ?>?v=<?= $time ?>" target="_blank">
                                                                <?= $file['name'] ?>
                                                            </a><br>
                                                        <?php }
                                                    } ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-lg-6">
                                            <div class="card bg-dark mb-2">
                                                <div class="card-header py-2">
                                                    <a href="<?= $mediaLinks['urls']['js']['header'] ?>" target="_blank" data-toggle="tooltip" data-placement="top" title="<?= $mediaLinks['paths']['js']['header'] ?>">
                                                        JS header
                                                    </a>
                                                </div>
                                                <div class="card-body py-1">
                                                    <?php
                                                    if ($links['js']['header']) {
                                                        foreach ($links['js']['header'] as $file) { ?>
                                                            <a href="<?= $asset.'dev/'.$file['name'] ?>?v=<?= $time ?>" target="_blank">
                                                                <?= $file['name'] ?>
                                                            </a><br>
                                                        <?php }
                                                    } ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-lg-6">
                                            <div class="card bg-dark">
                                                <div class="card-header py-2">
                                                    <a href="<?= $mediaLinks['urls']['js']['footer'] ?>" target="_blank" data-toggle="tooltip" data-placement="top" title="<?= $mediaLinks['paths']['js']['footer'] ?>">
                                                        JS footer
                                                    </a>
                                                </div>
                                                <div class="card-body py-1">
                                                    <?php
                                                    if ($links['js']['footer']) {
                                                        foreach ($links['js']['footer'] as $file) { ?>
                                                            <a href="<?= $asset.'dev/'.$file['name'] ?>?v=<?= $time ?>" target="_blank">
                                                                <?= $file['name'] ?>
                                                            </a><br>
                                                        <?php }
                                                    } ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- resources site -->
                                <div id="resources-site"
                                class="tab-pane fade py-2 <?= (Session::panel('box.tab.resources') == 'site' ? 'show active' : '') ?>">
                                    <?php
                                    $resources = array(
                                        'scss' => array(),
                                        'js'    => array(
                                            'header' => array(),
                                            'footer' => array()
                                        )
                                    );

                                    foreach (['layouts', 'mails', 'outcomes', 'pieces'] as $folder) {
                                        $filename = 'utils.'.substr($folder, 0, -1).'.json';

                                        foreach (File::rFolder($folder, ['json']) as $file) {
                                            if (basename($file) == $filename) {
                                                $utils = json_decode(file_get_contents($file), true);
                                                $file = preg_replace(
                                                    "~([^/]+/)(.*/)?([^/]+)~",
                                                    "<small class='text-muted'>$1</small><span class='text-light'>$2</span><span class='text-info'>$3</span>",
                                                    $file
                                                );

                                                // scss
                                                if (!empty($utils['scss']['ArshWell']) && is_array($utils['scss']['ArshWell'])) {
                                                    foreach ($utils['scss']['ArshWell'] as $path) {
                                                        $resources['scss'][$path][] = $file;
                                                    }
                                                }
                                                if (!empty($utils['scss']['project']) && is_array($utils['scss']['project'])) {
                                                    foreach ($utils['scss']['project'] as $path) {
                                                        $resources['scss'][$path][] = $file;
                                                    }
                                                }

                                                // js header
                                                if (!empty($utils['js']['header']['ArshWell']) && is_array($utils['js']['header']['ArshWell'])) {
                                                    foreach ($utils['js']['header']['ArshWell'] as $path) {
                                                        $resources['js']['header'][$path][] = $file;
                                                    }
                                                }
                                                if (!empty($utils['js']['header']['project']) && is_array($utils['js']['header']['project'])) {
                                                    foreach ($utils['js']['header']['project'] as $path) {
                                                        $resources['js']['header'][$path][] = $file;
                                                    }
                                                }

                                                // js footer
                                                if (!empty($utils['js']['footer']['ArshWell']) && is_array($utils['js']['footer']['ArshWell'])) {
                                                    foreach ($utils['js']['footer']['ArshWell'] as $path) {
                                                        $resources['js']['footer'][$path][] = $file;
                                                    }
                                                }
                                                if (!empty($utils['js']['footer']['project']) && is_array($utils['js']['footer']['project'])) {
                                                    foreach ($utils['js']['footer']['project'] as $path) {
                                                        $resources['js']['footer'][$path][] = $file;
                                                    }
                                                }
                                            }
                                        }
                                    } ?>

                                    <div class="row">
                                        <div class="col-12 col-xl-4">
                                            <div class="card bg-dark mb-2">
                                                <div class="card-header py-2">CSS</div>
                                                <div class="card-body py-1">
                                                    <?php
                                                    foreach ($resources['scss'] as $resource => $sources) { ?>
                                                        <a><span type="button" data-toggle="tooltip" data-placement="right" data-html="true" title="<div class='text-left'><?= implode('<br>', $sources) ?></div>">
                                                            <small class="text-muted">resources/scss/</small><?= $resource ?>.scss
                                                        </span></a><br>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-lg-6 col-xl-4">
                                            <div class="card bg-dark mb-2">
                                                <div class="card-header py-2">JS header</div>
                                                <div class="card-body py-1">
                                                    <?php
                                                    foreach ($resources['js']['header'] as $resource => $sources) { ?>
                                                        <a><span type="button" data-toggle="tooltip" data-placement="right" data-html="true" title="<div class='text-left'><?= implode('<br>', $sources) ?></div>">
                                                            <small class="text-muted">resources/js/</small><?= $resource ?>.js
                                                        </span></a><br>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-lg-6 col-xl-4">
                                            <div class="card bg-dark">
                                                <div class="card-header py-2">JS footer</div>
                                                <div class="card-body py-1">
                                                    <?php
                                                    foreach ($resources['js']['footer'] as $resource => $sources) { ?>
                                                        <a><span type="button" data-toggle="tooltip" data-placement="right" data-html="true" title="<div class='text-left'><?= implode('<br>', $sources) ?></div>">
                                                            <small class="text-muted">resources/js/</small><?= $resource ?>.js
                                                        </span></a><br>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div id="actions" class="tab-pane fade <?= (Session::panel('box.tab') == 'actions' ? 'active show' : '') ?>">
                            <!-- tabs -->
                            <ul class="nav nav-tabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link <?= (in_array(Session::panel('box.tab.actions'), [NULL, 'daily']) ? 'active' : '') ?>" data-toggle="tab" href="#actions-daily">
                                        Daily
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?= (Session::panel('box.tab.actions') == 'frequently' ? 'active' : '') ?>" data-toggle="tab" href="#actions-frequently">
                                        Frequently
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?= (Session::panel('box.tab.actions') == 'rarely' ? 'active' : '') ?>" data-toggle="tab" href="#actions-rarely">
                                        Rarely
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?= (Session::panel('box.tab.actions') == 'build' ? 'active' : '') ?>" data-toggle="tab" href="#actions-build">
                                        Build
                                    </a>
                                </li>
                            </ul>

                            <!-- Actions - tab contents -->
                            <div class="tab-content">
                                <!-- actions daily -->
                                <div id="actions-daily"
                                class="tab-pane fade py-2 <?= (in_array(Session::panel('box.tab.actions'), [NULL, 'daily']) ? 'show active' : '') ?>">
                                    <div class="row">
                                        <!-- pills -->
                                        <div class="col-sm-4">
                                            <div class="nav flex-column nav-pills" aria-orientation="vertical">
                                                <a href="#actions-daily-recompile" data-toggle="pill"
                                                class="nav-link btn-dark <?= (in_array(Session::panel('box.tab.actions.daily'), [NULL, 'recompile']) ? 'active show' : '') ?>">
                                                    Recompile existing css/js files
                                                </a>
                                                <a href="#actions-daily-crons" data-toggle="pill"
                                                class="nav-link btn-dark d-flex justify-content-between align-items-center <?= (Session::panel('box.tab.actions.daily') == 'crons' ? 'active show' : '') ?>">
                                                    See all CRONs
                                                    <?php $crons = count(File::rFolder('crons', ['php'])); ?>
                                                    <span class="rounded px-1 d-table text-center float-right btn-<?= ($crons ? 'info' : 'secondary') ?>">
                                                        <?= $crons ?>
                                                    </span>
                                                </a>
                                                <a href="#actions-daily-session" data-toggle="pill"
                                                class="nav-link btn-dark <?= (Session::panel('box.tab.actions.daily') == 'session' ? 'active show' : '') ?>">
                                                    Empty app session
                                                </a>
                                                <a href="#actions-daily-unlinked" data-toggle="pill"
                                                class="nav-link btn-dark <?= (Session::panel('box.tab.actions.daily') == 'unlinked' ? 'active show' : '') ?>">
                                                    Remove unlinked table files
                                                </a>
                                            </div>
                                        </div>

                                        <!-- Actions Daily - tab contents -->
                                        <div class="col-sm-8">
                                            <div class="tab-content">
                                                <!-- Recompile -->
                                                <form id="actions-daily-recompile" action="recompile-existing-css-js" max-attempts="<?= $max_attempts['recompile-css-js'] ?>"
                                                class="tab-pane fade <?= (in_array(Session::panel('box.tab.actions.daily'), [NULL, 'recompile']) ? 'active show' : '') ?>">
                                                    <button type="submit" class="btn btn-success loader py-1">Recompile existing css/js files</button>
                                                    <small class="d-block text-muted">It will take <i>at most</i> <?= Func::readableTime(count(Web::routes('GET')) * 15 * 1000) ?></small>
                                                    <div class="form-check mt-1">
                                                        <label class="form-check-label">
                                                            <input class="form-check-input" type="checkbox" checked disabled />
                                                            Remove routes from css/js that no longer exist
                                                        </label>
                                                    </div>

                                                    <div class="response collapse"><hr /></div> <!-- response -->
                                                </form>

                                                <!-- CRONs -->
                                                <div id="actions-daily-crons"
                                                class="tab-pane fade <?= (Session::panel('box.tab.actions.daily') == 'crons' ? 'active show' : '') ?>">
                                                    <small class="d-block text-muted">Only supervisors can run them <u>directly</u> (if dev TRUE).</small>

                                                    <?php
                                                    if (count(File::rFolder('crons', ['php'])) == 0) { ?>
                                                        <div class="alert alert-secondary mt-1 mb-0">
                                                            Do you need a Cron Job? Create a PHP file in crons/.
                                                        </div>
                                                    <?php }

                                                    $assoc = function (string $folder, bool $margin = false) use (&$assoc) {
                                                        echo '<ul class="list-group list-group-flush'. ($margin ? ' ml-4' : '') .'">';

                                                        foreach (glob($folder.'/*') as $f) {
                                                            if (is_dir($f)) {
                                                                echo '<li class="list-group-item list-group-item-dark text-light"><span class="font-weight-light">'. basename($f) .' &#8595;</span></li>';

                                                                $assoc($f, true);
                                                            }
                                                            else if (is_file($f) && File::extension($f) == 'php') {
                                                                echo '<li class="list-group-item list-group-item-dark"><a href="'. (Web::site() . $f) .'" target="_blank">'. basename($f) .'</a></li>';
                                                            }
                                                        }

                                                        echo '</ul>';
                                                    };
                                                    $assoc('crons');
                                                    ?>
                                                </div>

                                                <!-- Session -->
                                                <form id="actions-daily-session" action="empty-session"
                                                class="tab-pane fade <?= (Session::panel('box.tab.actions.daily') == 'session' ? 'active show' : '') ?>">
                                                    <button type="submit" class="btn btn-success loader py-1">Empty app session</button>
                                                    <div class="response collapse"><hr /></div> <!-- response -->
                                                </form>

                                                <!-- Remove unlinked files -->
                                                <form id="actions-daily-unlinked" action="remove-unlinked-files"
                                                class="tab-pane fade <?= (Session::panel('box.tab.actions.daily') == 'unlinked' ? 'active show' : '') ?>">
                                                    <button type="submit" class="btn btn-success loader py-1">Remove unlinked table files</button>
                                                    <div class="form-check mt-2">
                                                        <input class="form-check-input" type="checkbox" checked disabled />
                                                        <label class="form-check-label">
                                                            From uploads/.app/
                                                        </label>
                                                    </div>
                                                    <div class="form-check mb-2">
                                                        <label class="form-check-label" for="actions-daily-unlinked--remove-lg">
                                                            <input class="form-check-input" type="checkbox" name="remove-lg" id="actions-daily-unlinked--remove-lg" value="1" />
                                                            Remove unnecessary language files
                                                        </label>
                                                    </div>

                                                    <div class="response collapse"><hr /></div> <!-- response -->
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- actions frequently -->
                                <div id="actions-frequently"
                                class="tab-pane fade py-2 <?= (Session::panel('box.tab.actions') == 'frequently' ? 'show active' : '') ?>">
                                    <div class="row">
                                        <!-- pills -->
                                        <div class="col-sm-4">
                                            <div class="nav flex-column nav-pills" aria-orientation="vertical">
                                                <a href="#actions-frequently-tables" data-toggle="pill"
                                                class="nav-link btn-dark <?= (in_array(Session::panel('box.tab.actions.frequently'), [NULL, 'tables']) ? 'active show' : '') ?>">
                                                    Setup tables
                                                </a>
                                                <a href="#actions-frequently-backup" data-toggle="pill"
                                                class="nav-link btn-dark <?= (Session::panel('box.tab.actions.frequently') == 'backup' ? 'active show' : '') ?>">
                                                    Backup
                                                </a>
                                                <a href="#actions-frequently-download" data-toggle="pill"
                                                class="nav-link btn-dark <?= (Session::panel('box.tab.actions.frequently') == 'download' ? 'active show' : '') ?>">
                                                    Download project
                                                </a>
                                                <a href="#actions-frequently-directory" data-toggle="pill"
                                                class="nav-link btn-dark <?= (Session::panel('box.tab.actions.frequently') == 'directory' ? 'active show' : '') ?>">
                                                    Copy directory
                                                </a>
                                            </div>
                                        </div>

                                        <!-- tab contents -->
                                        <div class="col-sm-8">
                                            <div class="tab-content">

                                                <!-- Setup tables -->
                                                <form id="actions-frequently-tables" action="setup-tables"
                                                class="tab-pane fade <?= (in_array(Session::panel('box.tab.actions.frequently'), [NULL, 'tables']) ? 'active show' : '') ?>">
                                                    <button type="submit" class="btn btn-success loader py-1">Setup tables</button>
                                                    <div class="form-check mt-2">
                                                        <label class="form-check-label">
                                                            <input class="form-check-input" type="checkbox" checked disabled />
                                                            Sync modules with DB
                                                            <small><small class="d-block text-monospace">Looking for in outcomes/</small></small>
                                                            <div class="form-check">
                                                                <label class="form-check-label">
                                                                    <input class="form-check-input" type="checkbox" checked disabled />
                                                                    <small>Add non-existent language columns</small>
                                                                </label>
                                                            </div>
                                                            <div class="my-1 d-flex flex-wrap">
                                                                <div class="form-check form-check-inline">
                                                                    <small>Unused language columns:</small>
                                                                </div>
                                                                <div class="d-inline nowrap">
                                                                    <div class="form-check form-check-inline">
                                                                        <label class="form-check-label d-flex" for="actions-frequently-tables--remove-lg--0">
                                                                            <input class="form-check-input" type="radio" name="remove-lg" id="actions-frequently-tables--remove-lg--0" value="0" checked />
                                                                            <small>Make them DEFAULT NULL</small>
                                                                        </label>
                                                                    </div>
                                                                    <div class="form-check form-check-inline">
                                                                        <label class="form-check-label d-flex" for="actions-frequently-tables--remove-lg--1" data-toggle="tooltip" data-placement="top" title="Be careful!">
                                                                            <input class="form-check-input" type="radio" name="remove-lg" id="actions-frequently-tables--remove-lg--1" value="1" />
                                                                            <small>Remove them</small>
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </label>
                                                    </div>
                                                    <div class="form-check mt-1">
                                                        <label class="form-check-label">
                                                            <input class="form-check-input" type="checkbox" checked disabled />
                                                            Create and update validation tables
                                                            <small class="d-block">Looking for in App/ classes (<span class="strike-oblique strike-oblique-10g">App/Core</span>)</small>
                                                        </label>
                                                    </div>

                                                    <div class="response collapse"><hr /></div> <!-- response -->
                                                </form>

                                                <!-- Backup -->
                                                <form id="actions-frequently-backup" action="backup"
                                                class="tab-pane fade <?= (Session::panel('box.tab.actions.frequently') == 'backup' ? 'active show' : '') ?>">
                                                    <button type="submit" class="btn btn-success loader py-1">Backup</button>
                                                    <div class="form-check py-2">
                                                        <input class="form-check-input" type="checkbox" id="actions-frequently-backup--remove" data-toggle="tooltip" data-placement="left" title="Be careful!" />
                                                        <label class="form-check-label" for="actions-frequently-backup--remove" data-toggle="tooltip" data-placement="top" title="Be careful!">
                                                            Remove old local backups
                                                        </label>
                                                    </div>

                                                    <div class="response collapse"><hr /></div> <!-- response -->
                                                </form>

                                                <!-- Download project -->
                                                <form id="actions-frequently-download" action="download-project" max-attempts="<?= ceil(Folder::size('.') / 26214400) /* 25MB */ ?>"
                                                class="tab-pane fade <?= (Session::panel('box.tab.actions.frequently') == 'download' ? 'active show' : '') ?>">
                                                    <button type="submit" class="btn btn-success loader py-1">Download project</button>
                                                    <div class="row align-items-center text-muted my-2">
                                                        <div class="col-3 col-md-2 nowrap">Source:</div>
                                                        <div class="col-9 col-md-10">
                                                            <?= dirname(getcwd()) ?>/<b class="nowrap"><?= basename(getcwd()) ?></b>
                                                        </div>
                                                    </div>
                                                    <div class="row align-items-center text-muted">
                                                        <div class="col-3 col-md-2 nowrap">Archive:</div>
                                                        <div class="col-9 col-md-10">
                                                            <?= trim(ENV::root() ?: ENV::site(), '/') ?>
                                                            <span class="nowrap"><u>date("d.m.Y H-i")</u>.zip</span>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-1">
                                                        <div class="offset-3 col-9 offset-md-2 col-md-10">
                                                            <div class="form-check py-2">
                                                                <label class="form-check-label text-danger" for="actions-frequently-download--delete" data-toggle="tooltip" data-placement="left" title="Be careful!">
                                                                    <input class="form-check-input" disabled type="checkbox" name="delete" id="actions-frequently-download--delete" value="1" />
                                                                    And delete it from source: <span class="nowrap"><?= ENV::url() ?><span>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="response collapse"><hr /></div> <!-- response -->
                                                </form>

                                                <!-- Copy directory -->
                                                <form id="actions-frequently-directory" action="copy-directory"
                                                class="tab-pane fade <?= (Session::panel('box.tab.actions.frequently') == 'directory' ? 'active show' : '') ?>">
                                                    <button type="submit" class="btn btn-success loader py-1">Copy directory</button>
                                                    <div class="row align-items-center text-muted my-2">
                                                        <div class="col-3 col-lg-2 nowrap">Source:</div>
                                                        <div class="col-9 col-lg-10">
                                                            <input type="text" class="form-control" name="source" placeholder="What folder are you copying?" />
                                                        </div>
                                                        <div class="offset-3 offset-lg-2 col-9 col-lg-10">
                                                            <small class="text-danger" form-error="source"></small>
                                                        </div>
                                                    </div>
                                                    <div class="row align-items-center text-muted">
                                                        <div class="col-3 col-lg-2 nowrap">Destination:</div>
                                                        <div class="col-9 col-lg-10">
                                                            <input type="text" class="form-control" name="destination" placeholder="Where do you copy it?" />
                                                        </div>
                                                        <div class="offset-3 offset-lg-2 col-9 col-lg-10">
                                                            <small class="text-danger" form-error="destination"></small>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-1">
                                                        <div class="offset-3 col-9 offset-lg-2 col-lg-10">
                                                            <div class="form-check py-2">
                                                                <label class="form-check-label" for="actions-frequently-directory--mkdir">
                                                                    <input class="form-check-input" type="checkbox" name="mkdir" id="actions-frequently-directory--mkdir" value="1" />
                                                                    Make destination dirs recursively, if necessary
                                                                </label>
                                                            </div>
                                                            <div class="pb-2">
                                                                <div class="form-check form-check-inline">
                                                                    If destination exists:
                                                                </div>
                                                                <div class="d-inline nowrap">
                                                                    <div class="form-check form-check-inline">
                                                                        <label class="form-check-label" for="actions-frequently-directory--stop">
                                                                            <input class="form-check-input" type="radio" name="behavior" id="actions-frequently-directory--stop" value="stop" checked />
                                                                            Stop
                                                                        </label>
                                                                    </div>
                                                                    <div class="form-check form-check-inline">
                                                                        <label class="form-check-label" for="actions-frequently-directory--replace" data-toggle="tooltip" data-placement="top" title="Be careful!">
                                                                            <input class="form-check-input" type="radio" name="behavior" id="actions-frequently-directory--replace" value="replace" />
                                                                            Replace it
                                                                        </label>
                                                                    </div>
                                                                    <div class="form-check form-check-inline">
                                                                        <label class="form-check-label" for="actions-frequently-directory--merge" data-toggle="tooltip" data-placement="top" title="Be careful!">
                                                                            <input class="form-check-input" type="radio" name="behavior" id="actions-frequently-directory--merge" value="merge" />
                                                                            Merge them
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="response collapse"><hr /></div> <!-- response -->
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- actions rarely -->
                                <div id="actions-rarely"
                                class="tab-pane fade py-2 <?= (Session::panel('box.tab.actions') == 'rarely' ? 'show active' : '') ?>">
                                    <div class="row">
                                        <!-- pills -->
                                        <div class="col-sm-4">
                                            <div class="nav flex-column nav-pills" aria-orientation="vertical">
                                                <a href="#actions-rarely-copy" data-toggle="pill"
                                                class="nav-link btn-dark <?= (in_array(Session::panel('box.tab.actions.rarely'), [NULL, 'copy']) ? 'active show' : '') ?>">
                                                    Copy project
                                                </a>
                                                <a href="#actions-rarely-update" data-toggle="pill"
                                                class="nav-link btn-dark <?= (Session::panel('box.tab.actions.rarely') == 'update' ? 'active show' : '') ?>">
                                                    Update project
                                                </a>
                                                <a href="#actions-rarely-upgrade" data-toggle="pill"
                                                class="nav-link btn-dark <?= (Session::panel('box.tab.actions.rarely') == 'upgrade' ? 'active show' : '') ?>">
                                                    Update ArshWell kernel
                                                </a>
                                                <a href="#actions-rarely-reverse" data-toggle="pill"
                                                class="nav-link btn-dark <?= (Session::panel('box.tab.actions.rarely') == 'reverse' ? 'active show' : '') ?>">
                                                    Reverse text
                                                </a>
                                            </div>
                                        </div>

                                        <!-- tab contents -->
                                        <div class="col-sm-8">
                                            <div class="tab-content">
                                                <!-- Copy project -->
                                                <form id="actions-rarely-copy" action="copy-project"
                                                class="tab-pane fade <?= (in_array(Session::panel('box.tab.actions.rarely'), [NULL, 'copy']) ? 'active show' : '') ?>">
                                                    <button type="submit" class="btn btn-success loader py-1">Copy project</button>
                                                    <div class="row align-items-center text-muted my-2">
                                                        <div class="col-3 col-lg-2 nowrap">Source:</div>
                                                        <div class="col-9 col-lg-10">
                                                            <?= dirname(getcwd()) ?>/<b class="nowrap"><?= basename(getcwd()) ?></b>
                                                        </div>
                                                    </div>
                                                    <div class="row align-items-center text-muted">
                                                        <div class="col-3 col-lg-2 nowrap">Destination:</div>
                                                        <div class="col-9 col-lg-10">
                                                            <div class="input-group">
                                                                <div class="input-group-prepend" data-toggle="tooltip" data-placement="top" title="<?= dirname(getcwd()) ?>/">
                                                                    <span class="input-group-text px-1"><small><?= basename(dirname(getcwd())) ?>/</small></span>
                                                                </div>
                                                                <input type="text" class="form-control" name="folder" data-toggle="tooltip" data-placement="top" title="Filename will be urlencoded." />
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-1">
                                                        <div class="offset-3 col-9 offset-lg-2 col-lg-10">
                                                            <small class="text-danger" form-error="folder"></small>
                                                            <div class="form-check pt-2">
                                                                <input class="form-check-input" type="checkbox" name="replace" value="1" data-toggle="tooltip" data-placement="left" title="Be careful!" />
                                                                <label class="form-check-label">
                                                                    Replace, if necessary
                                                                </label>
                                                            </div>
                                                            <div class="form-check pb-2">
                                                                <input class="form-check-input" type="checkbox" data-toggle="tooltip" data-placement="left" title="Be careful!" />
                                                                <label class="form-check-label">
                                                                    Move it actually
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="response collapse"><hr /></div> <!-- response -->
                                                </form>

                                                <!-- Update project -->
                                                <form id="actions-rarely-update" action="update-project" max-attempts="<?= ceil(Folder::size('.') / 26214400) /* 25MB */ ?>"
                                                next="#actions-daily #actions-daily-recompile[action='recompile-existing-css-js']"
                                                class="tab-pane fade <?= (Session::panel('box.tab.actions.rarely') == 'update' ? 'active show' : '') ?>">
                                                    <button type="submit" class="btn btn-success loader py-1">Update project</button>

                                                    <div class="text-muted my-2">
                                                        <b>Tip:</b>
                                                        It is recommended a <u>SMART maintenance</u> to be prepared in advance <span class="nowrap">(10-30 min)</span>.
                                                    </div>
                                                    <div class="custom-file">
                                                        <input type="file" name="archive" empty-on-attempt="true" class="custom-file-input" id="actions-rarely-update--archive" />
                                                        <label class="custom-file-label" for="actions-rarely-update--archive">Choose file</label>
                                                    </div>
                                                    <small class="text-danger" form-error="archive"></small>
                                                    <div class="row mt-2">
                                                        <div class="col">
                                                            <div class="form-check">
                                                                <label class="form-check-label" for="actions-rarely-update--mode--improve">
                                                                    <input class="form-check-input" type="radio" name="replace" id="actions-rarely-update--mode--improve" value="0" checked />
                                                                    <b>Improve project</b>
                                                                </label>
                                                            </div>
                                                            <div class="form-check mt-1">
                                                                <label class="form-check-label">
                                                                    <input class="form-check-input" type="checkbox" checked disabled />
                                                                    Remove all caches
                                                                </label>
                                                            </div>
                                                            <div class="form-check my-1">
                                                                <label class="form-check-label">
                                                                    <input class="form-check-input" type="checkbox" checked disabled />
                                                                    Don't overwrite custom css/js
                                                                    <div><i><small>(just only classic ones)</small></i></div>
                                                                </label>
                                                            </div>
                                                            <div class="form-check my-1">
                                                                <label class="form-check-label">
                                                                    <input class="form-check-input" type="checkbox" checked disabled />
                                                                    Recompile css/js after update
                                                                </label>
                                                            </div>
                                                            <div class="form-check my-1">
                                                                <label class="form-check-label">
                                                                    <input class="form-check-input" type="checkbox" checked disabled />
                                                                    Don't overwrite table files
                                                                    <div><i><small>(from uploads/)</small></i></div>
                                                                </label>
                                                            </div>
                                                            <div class="form-check mt-1">
                                                                <label class="form-check-label">
                                                                    <input class="form-check-input" type="checkbox" checked disabled />
                                                                    Create new PHP session
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <div class="col-auto pt-2">
                                                            <div class="h-100 border border-secondary"></div>
                                                        </div>
                                                        <div class="col">
                                                            <div class="form-check">
                                                                <label class="form-check-label" for="actions-rarely-update--mode--replace" data-toggle="tooltip" data-placement="left" title="Be careful!">
                                                                    <input class="form-check-input" type="radio" name="replace" id="actions-rarely-update--mode--replace" value="1" />
                                                                    <small>Replace project</small>
                                                                </label>
                                                            </div>
                                                            <div class="form-check mt-1">
                                                                <label class="form-check-label">
                                                                    <input class="form-check-input" type="checkbox" checked disabled />
                                                                    Remove all caches
                                                                </label>
                                                            </div>
                                                            <div class="form-check my-1">
                                                                <label class="form-check-label text-secondary">
                                                                    <input class="form-check-input" type="checkbox" disabled />
                                                                    Don't overwrite custom css/js
                                                                    <div><i><small>(just only classic ones)</small></i></div>
                                                                </label>
                                                            </div>
                                                            <div class="form-check my-1">
                                                                <label class="form-check-label">
                                                                    <input class="form-check-input" type="checkbox" checked disabled />
                                                                    Recompile css/js after update
                                                                </label>
                                                            </div>
                                                            <div class="form-check my-1">
                                                                <label class="form-check-label text-secondary">
                                                                    <input class="form-check-input" type="checkbox" disabled />
                                                                    Don't overwrite table files
                                                                    <div><i><small>(from uploads/)</small></i></div>
                                                                </label>
                                                            </div>
                                                            <div class="form-check mt-1">
                                                                <label class="form-check-label">
                                                                    <input class="form-check-input" type="checkbox" checked disabled />
                                                                    Create new PHP session
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="response collapse"><hr /></div> <!-- response -->
                                                </form>

                                                <!-- Update ArshWell kernel -->
                                                <form id="actions-rarely-upgrade" action="upgrade-arshwell-kernel"
                                                class="tab-pane fade <?= (Session::panel('box.tab.actions.rarely') == 'upgrade' ? 'active show' : '') ?>">
                                                    <button type="submit" class="btn btn-success loader py-1">Update ArshWell kernel</button>

                                                    <div class="text-muted my-2">
                                                        Upgrades <u>App/Core/</u>, <u>DevTools/</u>,<br>
                                                        <u>resources/images/</u>, <u>resources/<i>...</i>/plugins/</u>,<br>
                                                        <u>.htaccess</u>, <u>download.php</u>, <u>web.php</u>.
                                                    </div>
                                                    <div class="custom-file">
                                                        <input type="file" name="archive" class="custom-file-input" id="actions-rarely-update--archive" />
                                                        <label class="custom-file-label" for="actions-rarely-update--archive">Choose file</label>
                                                    </div>
                                                    <small class="text-danger" form-error="archive"></small>

                                                    <div class="form-check my-1">
                                                        <label class="form-check-label" for="actions-rarely-upgrade--overwrite-resources">
                                                            <input class="form-check-input" type="checkbox" id="actions-rarely-upgrade--overwrite-resources" name="overwrite-resources" value="1" checked />
                                                            Overwrite resource images & scss/js plugins
                                                        </label>
                                                    </div>
                                                    <div class="form-check my-1">
                                                        <label class="form-check-label" for="actions-rarely-upgrade--hooks">
                                                            <input class="form-check-input" type="checkbox" id="actions-rarely-upgrade--hooks" name="hooks" value="1" checked />
                                                            Run upgrade hooks
                                                        </label>
                                                    </div>
                                                    <div class="form-check my-1">
                                                        <label class="form-check-label">
                                                            <input class="form-check-input" type="checkbox" checked disabled />
                                                            Remove <u>env.cache.json</u> and <u>forks.cache.json</u>
                                                        </label>
                                                    </div>
                                                    <div class="form-check mt-1 mb-2">
                                                        <label class="form-check-label">
                                                            <input class="form-check-input" type="checkbox" checked disabled />
                                                            Create new PHP session
                                                        </label>
                                                    </div>

                                                    <div class="response collapse"><hr /></div> <!-- response -->
                                                </form>

                                                <!-- Reverse text -->
                                                <form id="actions-rarely-reverse" action="reverse-text"
                                                class="tab-pane fade <?= (Session::panel('box.tab.actions.rarely') == 'reverse' ? 'active show' : '') ?>">
                                                    <button type="submit" class="btn btn-success loader py-1">Reverse text</button>

                                                    <div class="text-muted my-2">It's usefull for env DB data.</div>
                                                    <textarea class="form-control" name="text" placeholder="Insert text you want to reverse"></textarea>
                                                    <small class="text-danger mb-1" form-error="text"></small>

                                                    <div class="response collapse"><hr /></div> <!-- response -->
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- actions build -->
                                <div id="actions-build"
                                class="tab-pane fade py-2 <?= (Session::panel('box.tab.actions') == 'build' ? 'show active' : '') ?>">
                                    <?php
                                    if (!is_file('env.build.json')) { ?>
                                        <div class="alert alert-warning" role="alert">
                                            <h5 class="alert-heading">
                                                There is no <b>env.build.json</b> for building a new project.
                                            </h5>
                                            <p>This action does almost same thing as <strong>Download project</strong>.</p>
                                            <hr>
                                            <p class="mb-0">The only addition is that <u>env.json</u> will be merged with <u>env.build.json</u>.</p>
                                        </div>

                                        <div class="alert alert-dark" role="alert">
                                            <h5 class="alert-heading">What is the facility?</h5>
                                            <p class="mb-0">
                                                If you first create a demo project (using a dev domain),
                                                you can prepare the env that site will using on live.
                                                In that way, you can copy your project with correct env data (url, database, etc)
                                                every time you wanna update the live project.
                                            </p>
                                        </div>

                                        <sub class="position-absolute d-block text-muted">
                                            <b>Tip:</b> If you have been created the env.build.json, please reopen DevPanel.
                                        </sub>
                                    <?php }
                                    else if (!Func::hasValidJSON('env.build.json')) { ?>
                                        <div class="alert alert-danger" role="alert">
                                            <h5 class="alert-heading">
                                                Your <b>env.build.json</b> isn't valid
                                            </h5>
                                            <p class="mb-0">
                                                <strong>Error:</strong>
                                                <?php
                                                json_decode(file_get_contents('env.build.json'));
                                                switch (json_last_error()) {
                                                    case JSON_ERROR_NONE: {
                                                        echo "It disappeared. Reopen DevPanel.";
                                                        break;
                                                    }
                                                    case JSON_ERROR_DEPTH: {
                                                        echo "Maximum stack depth exceeded.";
                                                        break;
                                                    }
                                                    case JSON_ERROR_STATE_MISMATCH: {
                                                        echo "Underflow or the modes mismatch.";
                                                        break;
                                                    }
                                                    case JSON_ERROR_CTRL_CHAR: {
                                                        echo "Unexpected control character found.";
                                                        break;
                                                    }
                                                    case JSON_ERROR_SYNTAX: {
                                                        echo "Syntax error, malformed JSON.";
                                                        break;
                                                    }
                                                    case JSON_ERROR_UTF8: {
                                                        echo "Malformed UTF-8 characters, possibly incorrectly encoded.";
                                                        break;
                                                    }
                                                    default: {
                                                        echo "Unknown error";
                                                        break;
                                                    }
                                                } ?>
                                            </p>
                                        </div>

                                        <sub class="position-absolute d-block text-muted">
                                            <b>Tip:</b> If you have been updated the env.build.json, please reopen DevPanel.
                                        </sub>
                                    <?php }
                                    else { ?>
                                        <div class="row">
                                            <div class="col-sm-6 col-md-4 col-lg-3 mb-3">
                                                <form class="bg-info rounded-bottom" action="build/01.validate-input-data" next="#actions-build form[action='build/02.copy-project-in-build']">
                                                    <div class="row no-gutters align-items-center bg-light border border-light">
                                                        <div class="col-2 text-center text-info"><b>1.</b></div>
                                                        <div class="col-10 text-center bg-info">
                                                            <button type="submit" class="btn btn-success w-100 h-100 loader">Launch</button>
                                                        </div>
                                                    </div>
                                                    <div class="response collapse rounded-bottom bg-info px-2 pt-2 pb-1" style="font-size: smaller;"></div> <!-- response -->
                                                </form>
                                            </div>
                                            <div class="col-sm-6 col-md-4 col-lg-3 mb-3">
                                                <form class="bg-info rounded-bottom" action="build/02.copy-project-in-build" next="#actions-build form[action='build/03.merge-env-with-env-build']">
                                                    <div class="row no-gutters align-items-center bg-light border border-light">
                                                        <div class="col-2 text-center text-info"><b>2.</b></div>
                                                        <div class="col-10 p-1 text-center loader bg-info">
                                                            Copy project in build
                                                            <div class="form-check">
                                                                <label class="form-check-label" for="actions-build--css-js-files">
                                                                    <input class="form-check-input" type="checkbox" id="actions-build--css-js-files" name="css-js-files" checked value="1" />
                                                                    <small><small>Add ROUTE CSS/JS files</small></small>
                                                                </label>
                                                            </div>
                                                            <div class="form-check">
                                                                <label class="form-check-label" for="actions-build--table-files" data-toggle="tooltip" data-placement="top" title="For Update/Replace project">
                                                                    <input class="form-check-input" type="checkbox" id="actions-build--table-files" name="table-files" value="1" />
                                                                    <small><small>Add TABLE FILES</small></small>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="response collapse rounded-bottom bg-info px-2 pt-2 pb-1" style="font-size: smaller;"></div> <!-- response -->
                                                </form>
                                            </div>
                                            <div class="col-sm-6 col-md-4 col-lg-3 mb-3">
                                                <form class="bg-info rounded-bottom" action="build/03.merge-env-with-env-build" next="#actions-build form[action='build/04.recompile-css-js-files']">
                                                    <div class="row no-gutters align-items-center bg-light border border-light">
                                                        <div class="col-2 text-center text-info"><b>3.</b></div>
                                                        <div class="col-10 p-1 text-center loader bg-info">
                                                            Merge <u>env.json</u> with <u>env.build.json</u>
                                                        </div>
                                                    </div>
                                                    <div class="response collapse rounded-bottom bg-info px-2 pt-2 pb-1" style="font-size: smaller;"></div> <!-- response -->
                                                </form>
                                            </div>
                                            <div class="col-sm-6 col-md-4 col-lg-3 mb-3">
                                                <form class="bg-info rounded-bottom" max-attempts="<?= $max_attempts['recompile-css-js'] ?>"
                                                action="build/04.recompile-css-js-files" next="#actions-build form[action='build/05.remove-unlinked-files']">
                                                    <div class="row no-gutters align-items-center bg-light border border-light">
                                                        <div class="col-2 text-center text-info"><b>4.</b></div>
                                                        <div class="col-10 p-1 text-center loader bg-info" show-attempts="true">
                                                            Recompile css/js files
                                                        </div>
                                                    </div>
                                                    <div class="response collapse rounded-bottom bg-info px-2 pt-2 pb-1" style="font-size: smaller;"></div> <!-- response -->
                                                </form>
                                            </div>
                                            <div class="col-sm-6 col-md-4 col-lg-3 mb-3">
                                                <form class="bg-info rounded-bottom" action="build/05.remove-unlinked-files" next="#actions-build form[action='build/06.register-project-dev']">
                                                    <div class="row no-gutters align-items-center bg-light border border-light">
                                                        <div class="col-2 text-center text-info"><b>5.</b></div>
                                                        <div class="col-10 p-1 text-center loader bg-info">
                                                            Remove unlinked table files
                                                        </div>
                                                    </div>
                                                    <div class="response collapse rounded-bottom bg-info px-2 pt-2 pb-1" style="font-size: smaller;"></div> <!-- response -->
                                                </form>
                                            </div>
                                            <div class="col-sm-6 col-md-4 col-lg-3 mb-3">
                                                <form class="bg-info rounded-bottom" action="build/06.register-project-dev" next="#actions-build form[action='build/07.archive-entire-build-and-return-it']">
                                                    <div class="row no-gutters align-items-center bg-light border border-light">
                                                        <div class="col-2 text-center text-info"><b>6.</b></div>
                                                        <div class="col-10 p-1 text-center loader bg-info">
                                                            Register project development
                                                        </div>
                                                    </div>
                                                    <div class="response collapse rounded-bottom bg-info px-2 pt-2 pb-1" style="font-size: smaller;"></div> <!-- response -->
                                                </form>
                                            </div>
                                            <div class="col-sm-6 col-md-4 col-lg-3 mb-3">
                                                <form class="bg-info rounded-bottom" max-attempts="<?= ceil(Folder::size('.') / 26214400) /* 25MB */ ?>"
                                                action="build/07.archive-entire-build-and-return-it">
                                                    <div class="row no-gutters align-items-center bg-light border border-light">
                                                        <div class="col-2 text-center text-info"><b>7.</b></div>
                                                        <div class="col-10 p-1 text-center loader bg-info" show-attempts="true">
                                                            Archive entire build and return it
                                                        </div>
                                                    </div>
                                                    <div class="response collapse rounded-bottom bg-info px-2 pt-2 pb-1" style="font-size: smaller;"></div> <!-- response -->
                                                </form>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>

                        <!-- Warnings -->
                        <div id="warnings" class="tab-pane fade <?= (Session::panel('box.tab') == 'warnings' ? 'active show' : '') ?>">
                            <?php
                            if ((count($warnings, COUNT_RECURSIVE) - 3) == 0) { ?>
                                <div class="alert alert-primary" role="alert">
                                    <b>Good!</b> No errors for now :)
                                </div>
                            <?php } ?>

                            <?php
                            if ($warnings['errors']) { ?>
                                <div class="alert alert-danger" role="alert">
                                    PHP error files (<?= count($warnings['errors']) ?>).
                                    <a class="alert-link" data-toggle="collapse" href="#warnings--errors">
                                        Click to see them!
                                    </a>
                                    <div class="collapse" id="warnings--errors">
                                        <hr class="my-2">
                                        <?php
                                        foreach ($warnings['errors'] as $key => $file) { ?>
                                            <form class="my-1" action="delete-wrong-file">
                                                <input type="hidden" name="file" value="<?= $file ?>" />
                                                <div class="row align-items-end h-100">
                                                    <div class="col-6">
                                                        <a class="alert-link pt-1" data-toggle="collapse" role="button" data-target="#warnings--btn-errors-<?= $key ?>, #warnings--file-errors-<?= $key ?>">
                                                            <?= $file ?>
                                                        </a>
                                                    </div>
                                                    <div class="col-6 text-right">
                                                        <button type="submit" class="btn btn-sm btn-secondary collapse fade loader" id="warnings--btn-errors-<?= $key ?>">
                                                            Delete this file
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="response collapse mt-1" id="warnings--file-errors-<?= $key ?>">
                                                    <pre class="border border-secondary text-muted p-1 pr-2 mb-1"
                                                    style="max-height: 200px; max-height: 30vh;"><?= file_get_contents($file) ?></pre>
                                                </div>
                                            </form>
                                        <?php } ?>
                                    </div>
                                </div>
                            <?php }

                            if ($warnings['forbidden_files']) { ?>
                                <div class="alert alert-danger" role="alert">
                                    There are files in a <code>forbidden</code> place (<?= count($warnings['forbidden_files']) ?>).
                                    <a class="alert-link" data-toggle="collapse" href="#warnings--forbidden-files">
                                        Click to see them!
                                    </a>
                                    <div class="collapse" id="warnings--forbidden-files">
                                        <hr class="my-2">
                                        <?php
                                        foreach ($warnings['forbidden_files'] as $key => $file) { ?>
                                            <form class="my-1" action="delete-wrong-file">
                                                <input type="hidden" name="file" value="<?= $file ?>" />
                                                <div class="row align-items-end h-100">
                                                    <div class="col-6">
                                                        <a class="alert-link pt-1" data-toggle="collapse" role="button" data-target="#warnings--btn-forbidden-files-<?= $key ?>, #warnings--file-forbidden-files-<?= $key ?>">
                                                            <?= $file ?>
                                                        </a>
                                                    </div>
                                                    <div class="col-6 text-right">
                                                        <button type="submit" class="btn btn-sm btn-secondary collapse fade loader" id="warnings--btn-forbidden-files-<?= $key ?>">
                                                            Delete this file
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="response collapse mt-1" id="warnings--file-forbidden-files-<?= $key ?>">
                                                    <pre class="border border-secondary text-muted p-1 pr-2 mb-1"
                                                    style="max-height: 200px; max-height: 30vh;"><?= file_get_contents($file) ?></pre>
                                                </div>
                                            </form>
                                        <?php } ?>
                                    </div>
                                </div>
                            <?php }

                            if ($warnings['wrong_place_files']) { ?>
                                <div class="alert alert-warning" role="alert">
                                    There are files in a <code>wrong</code> place (<?= count($warnings['wrong_place_files']) ?>).
                                    <a class="alert-link" data-toggle="collapse" href="#warnings--wrong-place-files">
                                        Click to see them!
                                    </a>
                                    <div class="collapse" id="warnings--wrong-place-files">
                                        <hr class="my-2">
                                        <?php
                                        foreach ($warnings['wrong_place_files'] as $key => $file) { ?>
                                            <form class="my-1" action="delete-wrong-file">
                                                <input type="hidden" name="file" value="<?= $file ?>" />
                                                <div class="row align-items-end h-100">
                                                    <div class="col-6">
                                                        <a class="alert-link pt-1" data-toggle="collapse" role="button" data-target="#warnings--btn-wrong-place-files-<?= $key ?>, #warnings--file-wrong-place-files-<?= $key ?>">
                                                            <?= $file ?>
                                                        </a>
                                                    </div>
                                                    <div class="col-6 text-right">
                                                        <button type="submit" class="btn btn-sm btn-secondary collapse fade loader" id="warnings--btn-wrong-place-files-<?= $key ?>">
                                                            Delete this file
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="response collapse mt-1" id="warnings--file-wrong-place-files-<?= $key ?>">
                                                    <pre class="border border-secondary text-muted p-1 pr-2 mb-1"
                                                    style="max-height: 200px; max-height: 30vh;"><?= htmlspecialchars(file_get_contents($file)) ?></pre>
                                                </div>
                                            </form>
                                        <?php } ?>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>

                        <!-- Maintenance -->
                        <div id="maintenance" class="tab-pane fade <?= (Session::panel('box.tab') == 'maintenance' ? 'active show' : '') ?>">
                            <div class="row">
                                <div class="col-12 col-md-6 col-xl-5">
                                    <div class="card bg-dark mb-2">
                                        <div class="card-header py-2">
                                            Setup maintenance
                                        </div>
                                        <div class="card-body py-2">
                                            <form action="setup-maintenance">
                                                <button type="submit" class="btn btn-success loader py-1">Setup</button>
                                                <hr class="my-2" />
                                                <div class="row">
                                                    <div class="col-4">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" onclick="$('.maintenance--smart-configuration').collapse('hide');" name="type" id="maintenance--none" value="none" <?= (!ENV::maintenance('active') ? 'checked' : '') ?> />
                                                            <label class="form-check-label" for="maintenance--none">
                                                                None
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="col-4">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" onclick="$('.maintenance--smart-configuration').collapse('show');" name="type" id="maintenance--smart" value="smart" <?= (ENV::maintenance('active') && ENV::maintenance('smart') ? 'checked' : '') ?> />
                                                            <label class="form-check-label" for="maintenance--smart">
                                                                SMART
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="col-4">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" onclick="$('.maintenance--smart-configuration').collapse('hide');" name="type" id="maintenance--instant" value="instant" <?= (ENV::maintenance('active') && !ENV::maintenance('smart') ? 'checked' : '') ?> />
                                                            <label class="form-check-label" for="maintenance--instant">
                                                                Instant
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="card bg-dark maintenance--smart-configuration collapse <?= (ENV::maintenance('active') && ENV::maintenance('smart') ? 'show' : '') ?> mt-3">
                                                    <div class="card-header py-2">
                                                        SMART configuration
                                                    </div>
                                                    <div class="card-body py-2">
                                                        <label class="mb-1">Visible history</label>
                                                        <select name="sessions" class="form-control">
                                                            <option value="0" selected>For all sessions</option>
                                                            <?php
                                                            foreach (array_keys($sessions) as $session_id) { ?>
                                                                <option value="<?= $session_id ?>"><?= $session_id ?></option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="response collapse"><hr /></div> <!-- response -->
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6 col-xl-7">
                                    <div class="card bg-dark mb-2">
                                        <div class="card-header py-2">
                                            Routes accessed in real time
                                            <span class="maintenance--smart-configuration collapse <?= (ENV::maintenance('active') && ENV::maintenance('smart') ? 'show' : '') ?>">
                                                <?php // all sessions with history ?>
                                                (by all ~<?= count(array_filter(array_column(array_column($sessions, 'ArshWell'), 'history'))) ?> sessions)
                                            </span>
                                        </div>
                                        <div class="card-body py-0">
                                            <ul class="list-group list-group-flush maintenance--smart-configuration collapse <?= (ENV::maintenance('active') && ENV::maintenance('smart') ? 'show' : '') ?>">
                                                <?php
                                                foreach ($sessions as $session) {
                                                    foreach (array_reverse($session['ArshWell']['history']) as $index => $route) { ?>
                                                        <li type="button" class="list-group-item" data-toggle="collapse" data-target="#maintenance--history-<?= $index ?>" aria-expanded="false">
                                                            <div class="row">
                                                                <div class="col-3 col-md-3 nowrap">
                                                                    <?= $route['request'] ?>
                                                                </div>
                                                                <div class="col-1 nowrap">
                                                                    <?php
                                                                    if ($route['instances'] > 1) { ?>
                                                                        (<?= $route['instances'] ?>)
                                                                    <?php } ?>
                                                                </div>
                                                                <div class="col-8 col-md-7">
                                                                    <?= $route['key'] ?>
                                                                </div>
                                                                <div class="collapse w-100" id="maintenance--history-<?= $index ?>">
                                                                    <table class="table table-sm table-bordered table-dark m-0 mt-2">
                                                                        <?php
                                                                        // Routes can be changed during development.
                                                                        if (Web::exists($route['key'])) { ?>
                                                                            <tr>
                                                                                <th class="w-25">URL</th>
                                                                                <td class="w-75 break-word"><?= Web::url($route['key'], $route['params'], $route['language'], $route['page'], $route['$_GET']) ?></td>
                                                                            </tr>
                                                                        <?php } ?>
                                                                        <tr>
                                                                            <th class="w-25">page</th>
                                                                            <td class="w-75"><?= $route['page'] ?></td>
                                                                        </tr>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </li>
                                                    <?php }
                                                } ?>
                                            </ul>
                                            <small class="text-muted d-block my-2">They are shown only for SMART maintenance.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- History -->
                        <div id="history" class="tab-pane fade <?= (Session::panel('box.tab') == 'history' ? 'active show' : '') ?>">
                            <ul class="list-group">
                                <?php
                                foreach (array_reverse(Session::history()) as $index => $route) { ?>
                                    <li type="button" class="list-group-item" data-toggle="collapse" data-target="#history--<?= $index ?>" aria-expanded="false">
                                        <div class="row">
                                            <div class="col-2 col-md-1 nowrap">
                                                <?= $route['request'] ?>
                                            </div>
                                            <div class="col-1 nowrap">
                                                <?php
                                                if ($route['instances'] > 1) { ?>
                                                    (<?= $route['instances'] ?>)
                                                <?php } ?>
                                            </div>
                                            <div class="col-9 col-md-10">
                                                <?= $route['key'] ?>
                                            </div>
                                        </div>
                                        <div class="row no-gutters">
                                            <div class="offset-3 offset-md-2 col">
                                                <div class="collapse" id="history--<?= $index ?>">
                                                    <table class="table table-sm table-bordered table-dark m-0 mt-2">
                                                        <?php
                                                        // Routes can be changed during development.
                                                        if (Web::exists($route['key'])) { ?>
                                                            <tr>
                                                                <th class="w-25">URL</th>
                                                                <td class="w-75 break-word"><?= Web::url($route['key'], $route['params'], $route['language'], $route['page'], $route['$_GET']) ?></td>
                                                            </tr>
                                                        <?php }
                                                        if ($route['language']) { // only if has language ?>
                                                            <tr>
                                                                <th class="w-25">language</th>
                                                                <td class="w-75"><?= $route['language'] ?></td>
                                                            </tr>
                                                        <?php }
                                                        if ($route['page']) { // only if has pagination ?>
                                                            <tr>
                                                                <th class="w-25">page</th>
                                                                <td class="w-75"><?= $route['page'] ?></td>
                                                            </tr>
                                                        <?php } ?>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                <?php } ?>
                            </ul>
                        </div>

                        <!-- Process -->
                        <div id="process" class="tab-pane fade <?= (Session::panel('box.tab') == 'process' ? 'active show' : '') ?>">
                            <canvas id="process--chart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="d-block col-12 d-sm-none text-center">
                    <img style="max-width: 80%;" src="<?= URL::get(true, false) ?>?rshwll=<?= $_REQUEST['rshwll'] ?>&dvtls=fls&hdr=image/png&fl=images/ruler.png" />
                    <p>Please, use a <b>larger</b> screen.</p>
                </div>
            <?php } ?>
        </div>
    </div>
    <div class="card-footer text-muted">
        <div class="row align-items-center h-100">
            <div class="col-12 col-sm-10">
                <span class="advice">...</span>
            </div>
            <div class="d-none d-sm-block col-sm-2 nowrap text-right">
                <div class="float-right">
                    <?php
                    if (Session::panel('active')) {
                        /** projects ******************************************/
                            $projects = File::folder(sys_get_temp_dir(), ['zip'], false, false);
                            foreach ($projects as $key => $project) {
                                if (!preg_match("/^project_[a-zA-Z0-9-]+$/", $project)) {
                                    unset($projects[$key]);
                                }
                            }
                            $projects = count($projects);
                            if ($projects) { ?>
                                <span class="pl-1" data-toggle="tooltip" data-placement="left" data-title="<?= $projects ?> <?= ($projects == 1 ? 'session has been' : 'sessions have been') ?> downloaded the project recently">
                                    <span class="bg-primary border border-dark rounded text-dark d-inline-block text-center"
                                    style="width: 25px; height: 25px; font-size: 18px; line-height: 22px; cursor: help;">
                                        <b>!</b>
                                    </span>
                                </span>
                            <?php }
                        /** ↑ projects ****************************************/

                        /** builds ********************************************/
                            $builds = File::folder(sys_get_temp_dir(), ['zip'], false, false);
                            foreach ($builds as $key => $build) {
                                if (!preg_match("/^build_[a-zA-Z0-9-]+$/", $build)) {
                                    unset($builds[$key]);
                                }
                            }
                            $builds = count($builds);
                            if ($builds) { ?>
                                <span class="pl-1" data-toggle="tooltip" data-placement="left" data-title="<?= $builds ?> <?= ($builds == 1 ? 'session has been' : 'sessions have been') ?> made, and downloaded, builds recently">
                                    <span class="bg-warning border border-dark rounded text-dark d-inline-block text-center"
                                    style="width: 25px; height: 25px; font-size: 18px; line-height: 22px; cursor: help;">
                                        <b>!</b>
                                    </span>
                                </span>
                            <?php }
                        /** ↑ builds ******************************************/

                        /** supervisors ***************************************/
                            $supervisors = count(array_filter(array_column(array_column(array_column($sessions, 'ArshWell'), 'panel'), 'active')));
                            if ($supervisors) { ?>
                                <span class="pl-1" data-toggle="tooltip" data-placement="left" data-title="Another <?= $supervisors ?> session<?= ($supervisors > 1 ? 's' : '') ?> use DevPanel right now">
                                    <span class="bg-danger border border-dark rounded text-dark d-inline-block text-center"
                                    style="width: 25px; height: 25px; font-size: 18px; line-height: 22px; cursor: help;">
                                        <b>?</b>
                                    </span>
                                </span>
                            <?php }
                        /** ↑ supervisors *************************************/
                    } ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
if (Session::panel('active')) { // load js ?>
    <script type="text/javascript">
        'use strict';

        var max_microtime_sessions_history = "<?= max(call_user_func_array('array_merge_recursive', array_map('array_keys', array_filter(array_column(array_column($sessions, 'ArshWell'), 'history'))))) ?>";

        window.onload = function () {
            $('[data-toggle="tooltip"]').tooltip();

            (function () {
                // If you are in iframe
                if (window.location != window.parent.location) {
                    var link = $('a[data-href]:not([data-href=""]):not([href])');
                    link.attr('href', link.data('href'));

                    var advices = [
                        "<b>DevPanel</b> has <i>many tools</i> for developing a good project.",
                        "<b>Reminder:</b> Never forget about the <u><i>404</i></u> and <u><i>maintenance</i></u> pages.",
                        "<b>Important:</b> Don't change PHP class positions, if they have files.",
                        "<b>Important:</b> Don't change GET ROUTE names, because they use TableView class.",
                        "<b>Advice:</b> Set always best width ranges, in css/js filenames.",
                        "<b>Advice:</b> Don't close DevPanel during an action.",
                        "<b>History:</b> ArshWell&trade; started from the idea of a <i>fast and clean</i> framework.",
                        "<b>Info:</b> For uploading a new project version, first <b>turn maintenance on.</b>",
                        "<b>Info:</b> DevPanel content comes from the page load. <u>Reopen it</u>, if you wanna see updates.",
                        "<b>Brag:</b> <u>Layouts</u>, <u>pieces</u>, <u class='nowrap'>preset JS functions</u>, <u class='nowrap'>css/js compressing</u>, aren't the all <span class='nowrap'>super-powers.</span>",
                    ].sort(function () { return 0.5 - Math.random(); });

                    setInterval(function () {
                        var advice = advices.shift();
                            $(".card .card-footer .advice").fadeOut(0).html(advice).fadeIn(750);
                        advices.push(advice);
                    }(), 30000); // 30 seconds
                }
                // We are not in iframe
                else {
                    $(".card .card-footer .advice, .card .instance-of-panel")
                        .addClass('d-block').fadeOut(0).html(
                            "<b>This panel comes from <u>an instance</u> of "+
                            '<a href="'+ (window.location.origin + window.location.pathname) +'" target="_blank">' +
                                '<span'+ (window.location.pathname.length > 1 ? ' class="d-none d-lg-inline"' : '') +'>'+ (window.location.host || window.location.hostname) +'</span>' +
                                window.location.pathname +
                            "</a> (from "+
                            "<?= date(
                                    (date('Ymd', $info->value('time')) != date('Ymd') ? "d F " : '') .
                                    (date('Y', $info->value('time')) != date('Y') ? "Y " : '') .
                                    (date('Ymd H:i', $info->value('time')) != date('Ymd H:i') ? "H:i" : '\n\o\w'),
                                    $info->value('time')
                                 )
                            ?>" +
                            ")</b>"
                        ).fadeIn(750);
                }
            })();

            function getTable (json) {
                var table = document.createElement('table');
                table.style.display = "none";

                for (var key in json) {
                    if (json[key] != null) { // don't show NULL values
                        var tr = document.createElement('tr');

                            if (isNaN(key)) {
                                var th = document.createElement('th');
                                th.innerHTML = (key + ":");
                                tr.appendChild(th);
                            }

                            var td = document.createElement('td');
                            if (isNaN(key)) {
                                td.style.paddingLeft = "10px";
                            }
                            if (json[key].constructor == Object || Array.isArray(json[key])) {
                                td.appendChild(getTable(json[key]));
                            }
                            else {
                                td.innerHTML = json[key];
                            }
                            tr.appendChild(td);

                        table.appendChild(tr);
                    }
                }

                return table;
            }

            // saving current tab
            $(".card .card-body .nav .nav-link").on('click', function () {
                if (!$(this).hasClass('active')) {
                    var button = $(".tab-pane.active .loader.progress-bar-striped.progress-bar-animated");

                    if (button.length) {
                        var panel = button.closest('.tab-pane');

                        do {
                            var link = $(".nav .nav-link[href='#"+panel.attr('id')+"']");

                            // if $(this) isn't from this .nav, but this link is inactive
                            // or $(this) is from this .nav, but another link
                            if ((!link.closest(".nav").find($(this)).length && !link.hasClass('active'))
                            || link.closest(".nav").find($(this)).not($(link)).length) {
                                link.addClass("progress-bar-striped progress-bar-animated");
                            }
                            else {
                                link.removeClass("progress-bar-striped progress-bar-animated");
                            }

                            panel = link.closest('.tab-pane');
                        } while (panel.length);
                    }
                    else {
                        $.ajax({
                            url:    window.location.origin + window.location.pathname,
                            type:   'POST',
                            data:   {
                                rshwll: '<?= $_REQUEST['rshwll'] ?>',
                                pnl:    'AJAX/panel.tab',
                                tb:     this.getAttribute('href').substr(1)
                            }
                        });
                    }
                }
            });

            $("#actions, #warnings, #maintenance").find('form[action]').on('submit', function (event, prev = null) {
                event.preventDefault();

                window.onbeforeunload = function () {
                    return "Don't stop actions, no matter what!";
                };

                var target = this;
                var loader = $(target).find('.loader');
                var collapse = $(target).find('.response.collapse');
                var timeout = 250;

                $(loader).addClass('progress-bar-striped progress-bar-animated');
                $(collapse).addClass('text-muted');
                $("[type='submit']").prop('disabled', true);

                var form = new Form(this);

                form.syncErrors(function (element, error) {
                    $(element).css('opacity', '0');
                });

                if ($(target).attr('max-attempts') && (!$(target).attr('used-attempts')
                || $(target).attr('max-attempts') == $(target).attr('used-attempts'))) {
                    $(target).attr('used-attempts', '0');
                }

                if (!$(target).attr('request-time')) {
                    $(target).attr('request-time', (new Date()).getTime()); // in milliseconds
                }

                var data_extra = {
                    form_token: Form.token('form'),
                    rshwll:     '<?= $_REQUEST['rshwll'] ?>',
                    pnl:        'AJAX/actions/' + this.getAttribute('action'),
                    time:       $(target).attr('request-time'), // this feature appeared on 21/05/2021
                    attempt:    parseInt($(target).attr('used-attempts') || 0) + 1, // this feature appeared on 21/05/2021,
                    prev:       null // info about parent submit, if exists
                };
                if (prev) {
                    if (prev['pnl'] != data_extra['pnl']) {
                        data_extra['prev'] = prev;
                    }
                    else {
                        data_extra['prev'] = prev['prev'];
                    }
                }

                var data_json = form.serialize(data_extra, false);

                $.ajax({
                    url:            window.location.origin + window.location.pathname,
                    type:           'POST',
        			processData:	false,
        			contentType:	false,
                    dataType:       'JSON',
        			cache:			false,
                    data:           form.serialize(data_extra, true),
                    beforeSend: function() {
                        form.disable();
                    },
                    success: function (json) {
                        form.response(json);

                        form.syncValues();
                        form.syncErrors(function (element, error) {
                            $(element).html(error).animate({opacity: 1});
                        });

                        if (form.value('info')) {
                            $(collapse).find(':not(hr)').remove();
                            $(collapse).removeClass('text-muted');
                            $(collapse).append($(getTable(form.value('info')))).find('table').fadeIn();
                            $(collapse).collapse('show');
                        }
                        if (form.value('redirect') && 'href' in form.value('redirect')
                        && 'download' in form.value('redirect') && 'waiting' in form.value('redirect')) {
                            $('<a>').attr({
                                href:       form.value('redirect')['href'],
                                download:   form.value('redirect')['download']
                            })[0].click();

                            timeout = form.value('redirect')['waiting'];
                        }

                        setTimeout(function () {
                            $(loader).removeClass('progress-bar-striped progress-bar-animated');

                            form.enable();

                            if (form.valid() && $(target).attr('next')) {
                                setTimeout(function () {
                                    $($(target).attr('next')).trigger('submit', data_json);
                                }, 2000);
                            }
                            else {
                                $(target).removeAttr('request-time');
                                $(".nav .nav-link").removeClass("progress-bar-striped progress-bar-animated");
                                $("[type='submit']").prop('disabled', false).blur();

                                window.onbeforeunload = null;
                            }
                        }, timeout);

                        if ($(target).attr('max-attempts')) {
                            $(target).attr('used-attempts', '0');
                        }
                    },
                    error: function (response, type, error) {
                        if ($(target).attr('max-attempts')
                        && (parseInt($(target).attr('used-attempts')) + 1 < parseInt($(target).attr('max-attempts')))) {
                            $(target).attr('used-attempts', parseInt($(target).attr('used-attempts')) + 1);

                            var attempts = $(target).find('sub.attempts');

                            if (!attempts.length) {
                                var html = $('<sub class="attempts text-muted d-inline-block">1 attempt used</sub>');

                                if ($(target).find('[show-attempts="true"]').length) {
                                    $(target).find('[show-attempts="true"]').append(html);
                                }
                                else {
                                    $(html).insertBefore(collapse);
                                }
                            }
                            else {
                                $(attempts).html($(target).attr('used-attempts') + ' attempts used');
                            }

                            setTimeout(function () {
                                form.empty(function (field) {
                                    return ($(field).attr('empty-on-attempt') == 'true');
                                });
                                form.enable();
                                $(target).trigger('submit', data_json);
                            }, parseInt($(target).attr('used-attempts')) * 3000);
                        }
                        else {
                            $(target).removeAttr('request-time');

                            alert(error || response['status'] || response['statusText']);
                            console.log('🔽-🔽-🔽-🔽-🔽-🔽-🔽-🔽');
                            console.info('response:', response);
                            console.info('type:', type);
                            console.info('error:', error);
                            if (response.hasOwnProperty('responseText')) {
                                console.info('text:', response['responseText']);
                            }
                            console.info('status:', response['status'] || response['statusText']);
                            console.log('🔼-🔼-🔼-🔼-🔼-🔼-🔼-🔼');

                            window.onbeforeunload = null;
                        }
                    }
                })
            });

            // Adding file names at uploading input files.
            $(".custom-file input[type='file'].custom-file-input").on('change', function () {
                var label = $(this).siblings('.custom-file-label');
                if (!label.data('default-text')) {
                    label.data('default-text', label.html());

                    if (!label.data('files-text')) {
                        label.data('files-text', 'files');
                    }
                }

                var length = $(this)[0].files.length;

                if (!length) {
                    label.html(label.data('default-text'));
                    $(this).closest(".input-group").find(".custom-file-trash").removeClass('text-danger');
                }
                else {
                    if (length == 1) {
                        label.html('"'+ $(this)[0].files[0].name +'"');
                    }
                    else {
                        label.html(length +' '+ label.data('files-text'));
                    }

                    $(this).closest(".input-group").find(".custom-file-trash").addClass('text-danger');
                }
            });

            new Chart($("#process--chart"), {
            	type: 'line',
            	data: {
            		labels: ['Ianuarie', 'Februarie', 'Martie', 'Aprilie', 'Mai', 'Iunie', 'Iulie'],
            		datasets: [{
            			label: 'Analiza 1',
            			fill: false,
            			borderColor: 'rgb(255, 99, 132)',
            			backgroundColor: 'rgb(255, 99, 132)',
            			data: [
            				Math.random(),
            				Math.random(),
            				Math.random(),
            				Math.random(),
            				Math.random(),
            				Math.random(),
            				Math.random()
            			]
            		}, {
            			label: 'Analiza 2',
            			fill: false,
            			borderColor: 'rgb(54, 162, 235)',
            			backgroundColor: 'rgb(54, 162, 235)',
            			data: [
            				Math.random(),
            				Math.random(),
            				Math.random(),
            				Math.random(),
            				Math.random(),
            				Math.random(),
            				Math.random()
            			]
            		}]
            	},
            	options: {
            		title: {
            			display: true,
            			text: 'Rapoarte'
            		}
            	}
            });
        };
    </script>
<?php } ?>

<?php
_html(ob_get_clean(), Session::panel('active'));
