<?php
$resources = array(
    'scss' => array(),
    'js'    => array(
        'header' => array(),
        'footer' => array()
    )
);

foreach (['layouts', 'mails', 'outcomes', 'pieces'] as $folder) {
    $json_filename = 'utils.'.substr($folder, 0, -1).'.json';

    foreach (Arsavinel\Arshwell\File::rFolder($folder, ['json']) as $json_file) {
        if (basename($json_file) == $json_filename) {
            $utils = json_decode(file_get_contents($json_file), true);
            $json_file = preg_replace(
                "~([^/]+/)(.*/)?([^/]+)~",
                "<small class='text-muted'>$1</small><span class='text-light'>$2</span><span class='text-info'>$3</span>",
                $json_file
            );

            // scss
            if (!empty($utils['scss']['files']) && is_array($utils['scss']['files'])) {
                foreach ($utils['scss']['files'] as $folder => $files) {
                    foreach ($files as $scss_file) {
                        $resources['scss'][$folder][$scss_file][] = $json_file;
                    }
                }
            }

            // js header
            if (!empty($utils['js']['files']['header']) && is_array($utils['js']['files']['header'])) {
                foreach ($utils['js']['files']['header'] as $folder => $files) {
                    foreach ($files as $js_file) {
                        $resources['js']['header'][$folder][$js_file][] = $json_file;
                    }
                }
            }

            // js footer
            if (!empty($utils['js']['files']['footer']) && is_array($utils['js']['files']['footer'])) {
                foreach ($utils['js']['files']['footer'] as $folder => $files) {
                    foreach ($files as $js_file) {
                        $resources['js']['footer'][$folder][$js_file][] = $json_file;
                    }
                }
            }
        }
    }
} ?>

<div class="row">
    <div class="col-12">
        <div class="card bg-dark mb-2">
            <div class="card-header py-2">CSS</div>
            <div class="card-body py-1">
                <?php
                foreach ($resources['scss'] as $scss_folder => $files) {
                    foreach ($files as $scss_file => $jsons) { ?>
                        <div class="<?= (empty(glob("$scss_folder/scss/$scss_file.scss")) ? 'text-danger' : '') ?>">
                            <span style="cursor: help;" data-toggle="tooltip" data-placement="right" data-html="true" title="<div class='text-left'><?= implode('<br>', $jsons) ?></div>">
                                <small class='text-muted'><?= $scss_folder ?>/scss/</small><?= $scss_file ?>.scss
                                <small class='text-muted'>(<?= count($jsons) ?>)</small>
                            </span>
                        </div>
                    <?php }
                } ?>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="card bg-dark mb-2">
            <div class="card-header py-2">JS header</div>
            <div class="card-body py-1">
                <?php
                foreach ($resources['js']['header'] as $js_folder => $files) {
                    foreach ($files as $js_file => $jsons) { ?>
                        <div class="<?= (empty(glob("$js_folder/js/$js_file.js")) ? 'text-danger' : '') ?>">
                            <span style="cursor: help;" data-toggle="tooltip" data-placement="right" data-html="true" title="<div class='text-left'><?= implode('<br>', $jsons) ?></div>">
                                <small class='text-muted'><?= $js_folder ?>/js/</small><?= $js_file ?>.js
                                <small class='text-muted'>(<?= count($jsons) ?>)</small>
                            </span>
                        </div>
                    <?php }
                } ?>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="card bg-dark">
            <div class="card-header py-2">JS footer</div>
            <div class="card-body py-1">
                <?php
                foreach ($resources['js']['header'] as $js_folder => $files) {
                    foreach ($files as $js_file => $jsons) { ?>
                        <div class="<?= (empty(glob("$js_folder/js/$js_file.js")) ? 'text-danger' : '') ?>">
                            <span style="cursor: help;" data-toggle="tooltip" data-placement="right" data-html="true" title="<div class='text-left'><?= implode('<br>', $jsons) ?></div>">
                                <small class='text-muted'><?= $js_folder ?>/js/</small><?= $js_file ?>.js
                                <small class='text-muted'>(<?= count($jsons) ?>)</small>
                            </span>
                        </div>
                    <?php }
                } ?>
            </div>
        </div>
    </div>
</div>
