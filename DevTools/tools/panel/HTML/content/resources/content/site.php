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

    foreach (Arsavinel\Arshwell\File::rFolder($folder, ['json']) as $file) {
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
                    <a><span style="cursor: help;" data-toggle="tooltip" data-placement="right" data-html="true" title="<div class='text-left'><?= implode('<br>', $sources) ?></div>">
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
                    <a><span style="cursor: help;" data-toggle="tooltip" data-placement="right" data-html="true" title="<div class='text-left'><?= implode('<br>', $sources) ?></div>">
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
                    <a><span style="cursor: help;" data-toggle="tooltip" data-placement="right" data-html="true" title="<div class='text-left'><?= implode('<br>', $sources) ?></div>">
                        <small class="text-muted">resources/js/</small><?= $resource ?>.js
                    </span></a><br>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
