<?php

use ArshWell\Monolith\ENV;
use ArshWell\Monolith\Web;
use ArshWell\Monolith\File;
use ArshWell\Monolith\Func;
use ArshWell\Monolith\Folder;

$max_attempts = array(
    'recompile-css-js' => call_user_func(function () {
        $files = File::tree('uploads/design/css/');

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
?>

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
            you can prepare the env that website will using on live.
            In that way, you can copy your project with correct env data (url, database, etc)
            every time you wanna update the live project.

            <br><br>

            It also compiles the CSS/JS using env data from env.build.json
            <span class="nowrap">(ex: correct url in .css files).</span>
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

        <!-- Validate input data -->
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

        <!-- Copy project in build -->
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

        <!-- Merge env with env.build -->
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

        <!-- Recompile css/js files -->
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

        <!-- Remove unlinked table files -->
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

        <!-- Register project development -->
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

        <!-- Archive entire build and return it -->
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
