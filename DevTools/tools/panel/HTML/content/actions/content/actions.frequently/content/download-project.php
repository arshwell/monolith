<form action="download-project" max-attempts="<?= ceil(Arshwell\Monolith\Folder::size('.') / 26214400) /* 25MB */ ?>">
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
            <?= trim(Arshwell\Monolith\ENV::root() ?: Arshwell\Monolith\ENV::site(), '/') ?>
            <span class="nowrap"><u>date("d.m.Y H-i")</u>.zip</span>
        </div>
    </div>
    <div class="row mb-1">
        <div class="offset-3 col-9 offset-md-2 col-md-10">
            <div class="form-check py-2">
                <label class="form-check-label text-danger" for="actions-frequently-download--delete" data-toggle="tooltip" data-placement="left" title="Be careful!">
                    <input class="form-check-input" disabled type="checkbox" name="delete" id="actions-frequently-download--delete" value="1" />
                    And delete it from source: <span class="nowrap"><?= Arshwell\Monolith\ENV::url() ?><span>
                </label>
            </div>
        </div>
    </div>
    <div class="response collapse"><hr /></div> <!-- response -->
</form>
