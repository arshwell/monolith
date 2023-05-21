<?php

use Arshwell\Monolith\DevTool\DevToolData;
use Arshwell\Monolith\DevTool\DevToolHTML;
use Arshwell\Monolith\Table\TableValidation;
use Arshwell\Monolith\Session;
use Arshwell\Monolith\Time;
use Arshwell\Monolith\File;
use Arshwell\Monolith\StaticHandler;
use Arshwell\Monolith\URL;
use Arshwell\Monolith\Web;

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
                return Time::readableTime($value);
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
            // NOTE: some servers use 'text/plain' for JSON files
            if (!in_array(File::mimeType($file), ['application/json', 'text/plain', 'inode/x-empty'])) { // allow only json AND empty file
                $forbidden_files[] = $file;
            }
        }
        foreach (array('errors','config/forks','gates','layouts','mails','outcomes','pieces') as $folder) {
            foreach (File::rFolder($folder, [NULL]) as $file) {
                if (basename($file) == '.htaccess') {
                    $forbidden_files[] = $file;
                }
            }
        }
        foreach (File::rFolder('uploads', array(NULL, 'php', 'phtml')) as $file) {
            if (!in_array($file, [StaticHandler::getEnvConfig()->getFileStoragePathByIndex(0, 'uploads') . 'files/.htaccess', 'uploads/design/.htaccess'])
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
        foreach (File::rFolder('config/forks') as $file) {
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

ob_start(); // for adding all content in DevToolHTML::html() function
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
            box-shadow: inset 0 0 6px rgba(0, 0, 0, 0.3);
            -webkit-box-shadow: inset 0 0 6px rgba(0, 0, 0, 0.3);
            background-color: #555;
        }
        body .card .card-body pre::-webkit-scrollbar-track,
        body .card .card-body .tab-content.scrollable .tab-pane::-webkit-scrollbar-track {
            box-shadow: inset 0 0 6px rgba(0, 0, 0, 0.3);
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
                    if (Session::panel('active') && DevToolData::ArshwellVersion()) { ?>
                        <span class="text-danger"><?= DevToolData::ArshwellVersion() ?></span>
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

                            <span class="rounded px-1 d-table text-center float-right btn-<?= ((count($warnings['errors']) || count($warnings['forbidden_files'])) ? 'danger' : (count($warnings['wrong_place_files']) ? 'warning' : 'primary')) ?>">
                                <?= (count($warnings, COUNT_RECURSIVE) - 3) ?>
                            </span>
                        </a>
                        <a href="#maintenance" class="nav-link btn-dark <?= (Session::panel('box.tab') == 'maintenance' ? 'active show' : '') ?>" data-toggle="pill">
                            <div class="d-flex align-items-center">
                                Maintenance
                                <?php
                                if ((StaticHandler::getEnvConfig('services.maintenance'))::isActive()) { ?>
                                    <div class="spinner-grow spinner-grow-sm ml-auto <?= ((StaticHandler::getEnvConfig('services.maintenance'))::isSmart() ? 'text-success' : 'text-danger') ?> float-right" aria-hidden="true"></div>
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
                            <ul class="nav nav-tabs" HTML="info/tabs" role="tablist">

                            </ul>

                            <div class="tab-content">
                                <!-- info route -->
                                <div id="info-route" HTML="info/content/route"
                                class="tab-pane fade py-2 <?= (in_array(Session::panel('box.tab.info'), [NULL, 'route']) ? 'show active' : '') ?>">

                                </div>

                                <!-- info website -->
                                <div id="info-site" HTML="info/content/site"
                                class="tab-pane fade py-2 <?= (Session::panel('box.tab.info') == 'site' ? 'show active' : '') ?>">

                                </div>
                            </div>
                        </div>

                        <!-- Resources -->
                        <div id="resources" class="text-light tab-pane fade <?= (Session::panel('box.tab') == 'resources' ? 'active show' : '') ?>">
                            <!-- tabs -->
                            <ul class="nav nav-tabs" HTML="resources/tabs" role="tablist">

                            </ul>

                            <div class="tab-content">
                                <!-- resources route -->
                                <div id="resources-route" HTML="resources/content/route"
                                class="tab-pane fade py-2 <?= (in_array(Session::panel('box.tab.resources'), [NULL, 'route']) ? 'show active' : '') ?>">

                                </div>

                                <!-- resources website -->
                                <div id="resources-site" HTML="resources/content/site"
                                class="tab-pane fade py-2 <?= (Session::panel('box.tab.resources') == 'site' ? 'show active' : '') ?>">

                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div id="actions" class="tab-pane fade <?= (Session::panel('box.tab') == 'actions' ? 'active show' : '') ?>">
                            <!-- tabs -->
                            <ul class="nav nav-tabs" HTML="actions/tabs" role="tablist">

                            </ul>

                            <!-- Actions - tab contents -->
                            <div class="tab-content">
                                <!-- actions daily -->
                                <div id="actions-daily"
                                class="tab-pane fade py-2 <?= (in_array(Session::panel('box.tab.actions'), [NULL, 'daily']) ? 'show active' : '') ?>">
                                    <div class="row">
                                        <!-- pills -->
                                        <div class="col-sm-4">
                                            <div class="nav flex-column nav-pills" HTML="actions/content/actions.daily/tabs" aria-orientation="vertical">

                                            </div>
                                        </div>

                                        <!-- Actions Daily - tab contents -->
                                        <div class="col-sm-8">
                                            <div class="tab-content">
                                                <!-- Recompile existing css/js -->
                                                <div id="actions-daily-recompile" HTML="actions/content/actions.daily/content/recompile-existing-css-js"
                                                class="tab-pane fade <?= (in_array(Session::panel('box.tab.actions.daily'), [NULL, 'recompile']) ? 'active show' : '') ?>">

                                                </div>

                                                <!-- CRONs -->
                                                <div id="actions-daily-crons" HTML="actions/content/actions.daily/content/crons"
                                                class="tab-pane fade <?= (Session::panel('box.tab.actions.daily') == 'crons' ? 'active show' : '') ?>">

                                                </div>

                                                <!-- Session -->
                                                <div id="actions-daily-session" HTML="actions/content/actions.daily/content/session"
                                                class="tab-pane fade <?= (Session::panel('box.tab.actions.daily') == 'session' ? 'active show' : '') ?>">

                                                </div>

                                                <!-- Remove unlinked files -->
                                                <div id="actions-daily-unlinked" HTML="actions/content/actions.daily/content/remove-unlinked-files"
                                                class="tab-pane fade <?= (Session::panel('box.tab.actions.daily') == 'unlinked' ? 'active show' : '') ?>">

                                                </div>
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
                                            <div class="nav flex-column nav-pills" HTML="actions/content/actions.frequently/tabs" aria-orientation="vertical">

                                            </div>
                                        </div>

                                        <!-- tab contents -->
                                        <div class="col-sm-8">
                                            <div class="tab-content">

                                                <!-- Setup tables -->
                                                <div id="actions-frequently-tables" HTML="actions/content/actions.frequently/content/setup-tables"
                                                class="tab-pane fade <?= (in_array(Session::panel('box.tab.actions.frequently'), [NULL, 'tables']) ? 'active show' : '') ?>">

                                                </div>

                                                <!-- Backup data -->
                                                <div id="actions-frequently-backup" HTML="actions/content/actions.frequently/content/backup"
                                                class="tab-pane fade <?= (Session::panel('box.tab.actions.frequently') == 'backup' ? 'active show' : '') ?>">

                                                </div>

                                                <!-- Download project -->
                                                <div id="actions-frequently-download" HTML="actions/content/actions.frequently/content/download-project"
                                                class="tab-pane fade <?= (Session::panel('box.tab.actions.frequently') == 'download' ? 'active show' : '') ?>">

                                                </div>

                                                <!-- Copy directory -->
                                                <div id="actions-frequently-directory" HTML="actions/content/actions.frequently/content/copy-directory"
                                                class="tab-pane fade <?= (Session::panel('box.tab.actions.frequently') == 'directory' ? 'active show' : '') ?>">

                                                </div>
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
                                            <div class="nav flex-column nav-pills" HTML="actions/content/actions.rarely/tabs" aria-orientation="vertical">

                                            </div>
                                        </div>

                                        <div class="col-sm-8">
                                            <div class="tab-content">
                                                <!-- Copy project -->
                                                <div id="actions-rarely-copy" HTML="actions/content/actions.rarely/content/copy-project"
                                                class="tab-pane fade <?= (in_array(Session::panel('box.tab.actions.rarely'), [NULL, 'copy']) ? 'active show' : '') ?>">

                                                </div>

                                                <!-- Update project -->
                                                <div id="actions-rarely-update" HTML="actions/content/actions.rarely/content/update-project"
                                                class="tab-pane fade <?= (Session::panel('box.tab.actions.rarely') == 'update' ? 'active show' : '') ?>">

                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- actions build -->
                                <div id="actions-build" HTML="actions/content/actions.build"
                                class="tab-pane fade py-2 <?= (Session::panel('box.tab.actions') == 'build' ? 'show active' : '') ?>">

                                </div>
                            </div>
                        </div>

                        <!-- Warnings -->
                        <div id="warnings" HTML="warnings" class="tab-pane fade <?= (Session::panel('box.tab') == 'warnings' ? 'active show' : '') ?>">

                        </div>

                        <!-- Maintenance -->
                        <div id="maintenance" HTML="maintenance" class="tab-pane fade <?= (Session::panel('box.tab') == 'maintenance' ? 'active show' : '') ?>">

                        </div>

                        <!-- History -->
                        <div id="history" HTML="history" class="tab-pane fade <?= (Session::panel('box.tab') == 'history' ? 'active show' : '') ?>">

                        </div>

                        <!-- Process -->
                        <div id="process" HTML="process" class="tab-pane fade <?= (Session::panel('box.tab') == 'process' ? 'active show' : '') ?>">

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
                <span class="advice-or-instance-of-panel">...</span>
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
                            $supervisors = count(array_filter(array_column(array_column(array_column(array_column($sessions, 'vendor'), 'Arshwell'), 'panel'), 'active')));
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

        var max_microtime_sessions_history = "<?= max(call_user_func_array('array_merge_recursive', array_map('array_keys', array_filter(array_column(array_column(array_column($sessions, 'vendor'), 'Arshwell'), 'history'))))) ?>";

        window.onload = function () {
            $('[data-toggle="tooltip"]').tooltip();
            fn.showAdviceOrInstance();

            // on page load
            $(".nav[html]").filter(":visible").each(function () {
                fn.fillHtmlWithAJAX(this);
            });
            $(".tab-pane.active").filter(":visible").each(function () {
                var trigger = $("[href='#" + $(this).attr('id').split('-')[0] + "']");

                fn.fillHtmlWithAJAX(this, trigger.length ? trigger : null);
            });

            // saving current tab
            $(document).on('click', ".card .card-body .nav .nav-link", function () {
                fn.saveTabAndFillHtml(this);
            });

            $(document).on('submit', "form[action]", function (event, prev = null) {
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
                            $(collapse).append($(fn.getTable(form.value('info')))).find('table').fadeIn();
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

            if ($("#process--chart").length) {
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
            }
        };

        var fn = {
            /**
             *
             */
            showAdviceOrInstance: function (parent = null) {
                if (parent == null) {
                    parent = $('body');
                }

                // If you are in iframe
                if (window.location != window.parent.location) {
                    var link = $('a[data-href]:not([data-href=""]):not([href])');
                    link.attr('href', link.data('href'));

                    var advices = [
                        "<b>DevPanel</b> has <i>many tools</i> for developing a good project.",
                        "<b>Reminder:</b> Never forget about the <u><i>404</i></u> and <u><i>maintenance</i></u> pages.",
                        "<b>Important:</b> Don't change PHP class positions in file tree, if they have files.",
                        "<b>Important:</b> Don't change GET ROUTE names, because they use TableView class.",
                        "<b>Advice:</b> Set always best width ranges, in css/js filenames.",
                        "<b>Advice:</b> Don't close DevPanel during an action.",
                        "<b>History:</b> Arshwell&trade; started from the idea of a <i>fast and clean</i> framework.",
                        "<b>Info:</b> For uploading a new project version, first <b>turn maintenance on.</b>",
                        "<b>Info:</b> DevPanel content comes from the page load. <u>Reopen it</u>, if you wanna see updates.",
                        "<b>Brag:</b> <u>Layouts</u>, <u>pieces</u>, <u class='nowrap'>preset JS functions</u>, <u class='nowrap'>css/js compressing</u>, aren't the all <span class='nowrap'>super-powers.</span>",
                    ].sort(function () { return 0.5 - Math.random(); });

                    setInterval(function () {
                        var advice = advices.shift();

                        $(parent).find(".advice-or-instance-of-panel").fadeOut(0).html(advice).fadeIn(750);

                        advices.push(advice);
                    }(), 30000); // 30 seconds
                }
                // We are not in iframe
                else {
                    $(parent).find(".advice-or-instance-of-panel, .instance-of-panel")
                        .addClass('d-block').fadeOut(0).html(
                            "<b>This panel comes from <u>an instance</u> of "+
                                '<a href="'+ (window.location.origin + window.location.pathname) +'" target="_blank">' +
                                    '<span'+ (window.location.pathname.length > 1 ? ' class="d-none d-lg-inline"' : '') +'>'+ (window.location.host || window.location.hostname) +'</span>' +
                                    window.location.pathname +
                                "</a> (from "+
                                "<?= Time::readableDate($info->value('time')) ?>" +
                            ")</b>"
                        ).fadeIn(700);
                }
            },

            /**
             *
             */
            getTable: function (json, style = {}) {
                var table = document.createElement('table');
                table.style.display = "none";

                for (const property in style) {
                    table.style[property] = style[property];
                }

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
                            else {
                                td.setAttribute("colspan", 2);
                            }

                            if (json[key].constructor == Object || Array.isArray(json[key])) {
                                td.appendChild(fn.getTable(json[key]));
                            }
                            else {
                                td.innerHTML = json[key];
                            }

                            tr.appendChild(td);

                        table.appendChild(tr);
                    }
                }

                return table;
            },

            /**
             *
             */
            fillHtmlWithAJAX: function (element, trigger = null) {
                if ($(element).attr('html-fetched-at') == undefined) {
                    // Check to see if the counter has been initialized
                    if (typeof fn.fillHtmlWithAJAX.running == 'undefined') {
                        fn.fillHtmlWithAJAX.running = 0;
                    }

                    var HtmlFilePath = $(element).attr('html');

                    if (HtmlFilePath) {
                        var response = null;

                        $.ajax({
                            url:            window.location.origin + window.location.pathname,
                            type:           'POST',
                            dataType:       'html',
                            data:           {
                                rshwll:     '<?= $_REQUEST['rshwll'] ?>',
                                pnl:        'HTML/content/'+ HtmlFilePath,
                                request:    JSON.parse('<?= json_encode($info->values()) ?>')
                            },

                            beforeSend: function() {
                                if (trigger) {
                                    $(trigger).addClass('progress-bar-animated progress-bar-striped');
                                }

                                fn.fillHtmlWithAJAX.running++;
                            },

                            // save response (success / error)
                            complete: function (event, status) {
                                response = {
                                    'event': event,
                                    'status': status
                                };

                                fn.fillHtmlWithAJAX.running--;
                            },

                            error: function (response, type, error) {
                                console.log('⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️');
                                console.info('pnl:', 'HTML/content/'+ HtmlFilePath);
                                console.info('type:', type);
                                console.info('error:', error);
                                if (response.hasOwnProperty('responseText')) {
                                    console.info('text:', response['responseText']);
                                }
                                console.info('status:', response['status'] || response['statusText']);
                                console.info('response:', response);
                                console.log('⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️');

                                $(element)
                                    .html(fn.getTable(
                                        {
                                            0: "<b>AJAX RESPONSE</b>",
                                            type: type + " ⚠️",
                                            status: (response['status'] || response['statusText']),
                                            error: error ? error : undefined,
                                            text: response.hasOwnProperty('responseText') ? (response['responseText'] ? response.text : undefined) : undefined
                                        },
                                        {display: "block", borderLeft: "1px solid red", paddingLeft: "7px"}
                                    ))
                                    .attr('html-fetched-at', (new Date).getTime());

                                window.onbeforeunload = null;
                            }
                        });

                        // checking which was the AJAX response
                        var checkingInterval = setInterval(function () {
                            if (response) {

                                if (response.status == 'success') {
                                    // NOTE: fadeIn() doesn't work properly
                                    $(element).hide().html(response.event.responseText).fadeIn(700).attr('html-fetched-at', (new Date).getTime());
                                    fn.showAdviceOrInstance(element);

                                    clearInterval(checkingInterval);
                                }

                                if (trigger && fn.fillHtmlWithAJAX.running == 0) {
                                    $(element).find('[data-toggle="tooltip"]').tooltip();

                                    setTimeout(function () {
                                        $(trigger).removeClass('progress-bar-animated progress-bar-striped');
                                    }, 100);
                                }
                            }
                        }, 300); // we do that so loading animation has a least time to run
                    }
                }
            },

            /**
             *
             */
            saveTabAndFillHtml: function (trigger) {
                var trigger = $(trigger);
                var href = trigger.attr('href');
                var pane = $(href);

                fn.fillHtmlWithAJAX(pane, trigger);

                $(pane).find(".nav[html]").each(function () {
                    fn.fillHtmlWithAJAX(this, trigger);
                });
                $(pane).find("tab-pane.active .tab-pane.active[html]").each(function () {
                    fn.fillHtmlWithAJAX(this, trigger);
                });

                // NOTE: not works well
                // it gets also panes who has other panes as parents
                $(pane).find("*:not(.tab-pane) .tab-pane.active[html]").each(function () {
                    fn.fillHtmlWithAJAX(this, trigger);
                });

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
                            tb:     href.substr(1)
                        }
                    });
                }
            }
        }; // end of `fn` var
    </script>
<?php } ?>

<?php
DevToolHTML::html(ob_get_clean(), Session::panel('active'));
