<?php

use Arshwell\Monolith\Layout;
use Arshwell\Monolith\Web;
use Arshwell\Monolith\StaticHandler;

$resources = array(
    'scss' => array(),
    'js'    => array(
        'header' => array(),
        'footer' => array()
    )
);

$utils = Layout::mediaSCSS(Web::folder($_REQUEST['request']['route']), $_REQUEST['request']['pieces'] ?? array(), true)['json'];

// scss
if (!empty($utils['scss']['files']) && is_array($utils['scss']['files'])) {
    foreach ($utils['scss']['files'] as $folder => $files) {
        foreach ($files as $scss_file) {
            $resources['scss'][$folder][] = $scss_file;
        }
    }
}

// js header
if (!empty($utils['js']['files']['header']) && is_array($utils['js']['files']['header'])) {
    foreach ($utils['js']['files']['header'] as $folder => $files) {
        foreach ($files as $js_file) {
            $resources['js']['header'][$folder][] = $js_file;
        }
    }
}

// js footer
if (!empty($utils['js']['files']['footer']) && is_array($utils['js']['files']['footer'])) {
    foreach ($utils['js']['files']['footer'] as $folder => $files) {
        foreach ($files as $js_file) {
            $resources['js']['footer'][$folder][] = $js_file;
        }
    }
}

$asset  = StaticHandler::getEnvConfig()->getRoot().'/'.'uploads/design/dev/';
$time   = substr(str_shuffle("BCDFGHKLMNPQRSTVWXYZ"), 0, 4);

$mediaLinks = Layout::mediaLinks($_REQUEST['request']['route'], $_REQUEST['request']['pieces'] ?? array()); ?>

<span class="text-muted mb-2 instance-of-panel"></span> <!-- the link we came from -->

<div class="row">
    <div class="col-12">
        <div class="card bg-dark mb-2">
            <div class="card-header py-2">
                <a href="<?= $mediaLinks['urls']['css'] ?>" target="_blank" data-toggle="tooltip" data-placement="top" title="<?= $mediaLinks['paths']['css'] ?>">
                    CSS
                </a>
            </div>
            <div class="card-body py-1">
                <?php
                foreach ($resources['scss'] as $scss_folder => $files) {
                    foreach ($files as $scss_file) { ?>
                        <a href="<?= $asset . $scss_folder .'/scss/'. $scss_file ?>.css?v=<?= $time ?>" target="_blank">
                            <div class="<?= (empty(glob("$scss_folder/scss/$scss_file.scss")) ? 'text-danger' : '') ?>">
                                <small class='text-muted'><?= $scss_folder ?>/scss/</small><?= $scss_file ?>.css
                            </div>
                        </a>
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
                foreach ($resources['js']['header'] as $js_folder => $files) {
                    foreach ($files as $js_file) { ?>
                        <a href="<?= $asset . $js_folder .'/js/'. $js_file ?>.js?v=<?= $time ?>" target="_blank">
                            <div class="<?= (empty(glob("$js_folder/js/$js_file.js")) ? 'text-danger' : '') ?>">
                                <small class='text-muted'><?= $js_folder ?>/js/</small><?= $js_file ?>.js
                            </div>
                        </a>
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
                foreach ($resources['js']['footer'] as $js_folder => $files) {
                    foreach ($files as $js_file) { ?>
                        <a href="<?= $asset . $js_folder .'/js/'. $js_file ?>.js?v=<?= $time ?>" target="_blank">
                            <div class="<?= (empty(glob("$js_folder/js/$js_file.js")) ? 'text-danger' : '') ?>">
                                <small class='text-muted'><?= $js_folder ?>/js/</small><?= $js_file ?>.js
                            </div>
                        </a>
                    <?php }
                } ?>
            </div>
        </div>
    </div>
</div>
