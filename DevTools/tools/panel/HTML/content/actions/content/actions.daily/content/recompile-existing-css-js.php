<?php

use ArshWell\Monolith\ENV;
use ArshWell\Monolith\Web;
use ArshWell\Monolith\File;
use ArshWell\Monolith\Time;

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

<form action="recompile-existing-css-js" max-attempts="<?= $max_attempts['recompile-css-js'] ?>">
    <button type="submit" class="btn btn-success loader py-1">Recompile existing css/js files</button>
    <small class="d-block text-muted">It will take <i>at most</i> <?= Time::readableTime(count(Web::routes('GET')) * 15 * 1000) ?></small>
    <div class="form-check mt-1">
        <label class="form-check-label">
            <input class="form-check-input" type="checkbox" checked disabled />
            Remove routes from css/js that no longer exist
        </label>
    </div>

    <div class="response collapse"><hr /></div> <!-- response -->
</form>
