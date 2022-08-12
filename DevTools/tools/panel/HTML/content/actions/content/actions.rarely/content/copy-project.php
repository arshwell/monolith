<form action="copy-project">
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
