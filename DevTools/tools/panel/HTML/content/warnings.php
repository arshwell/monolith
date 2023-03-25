<?php

use Arshwell\Monolith\StaticHandler;
use Arshwell\Monolith\File;

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
                $forbidden_files[] = $file . " (".File::mimeType($file).")";
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
            if (!in_array($file, [StaticHandler::getEnvConfig()->getLocationPath('uploads') . 'files/'.'.htaccess', 'uploads/design/.htaccess'])
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
?>

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
