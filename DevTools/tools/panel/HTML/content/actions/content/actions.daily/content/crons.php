<small class="d-block text-muted">Only supervisors can run them <u>directly</u> (if dev TRUE).</small>

<?php
if (count(Arsavinel\Arshwell\File::rFolder('crons', ['php'])) == 0) { ?>
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
            echo '<li class="list-group-item list-group-item-dark"><a href="'. (Arsavinel\Arshwell\Web::site() . $f) .'" target="_blank">'. basename($f) .'</a></li>';
        }
    }

    echo '</ul>';
};
$assoc('crons');
?>
