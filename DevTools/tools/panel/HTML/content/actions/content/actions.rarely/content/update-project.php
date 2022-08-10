<form action="update-project" max-attempts="<?= ceil(Arsh\Core\Folder::size('.') / 26214400) /* 25MB */ ?>" next="#actions-daily-recompile form[action]">
    <button type="submit" class="btn btn-success loader py-1">Update project</button>

    <div class="text-muted my-2">
        <b>Tip:</b>
        It is recommended a <u>SMART maintenance</u> to be prepared in advance <span class="nowrap">(10-30 min)</span>.
    </div>
    <div class="custom-file">
        <input type="file" name="archive" empty-on-attempt="true" class="custom-file-input" id="actions-rarely-update--archive" />
        <label class="custom-file-label" for="actions-rarely-update--archive">Choose file</label>
    </div>
    <small class="text-danger" form-error="archive"></small>
    <div class="row mt-2">
        <div class="col">
            <div class="form-check">
                <label class="form-check-label" for="actions-rarely-update--mode--improve">
                    <input class="form-check-input" type="radio" name="replace" id="actions-rarely-update--mode--improve" value="0" checked />
                    <b>Improve project</b>
                </label>
            </div>
            <div class="form-check mt-1">
                <label class="form-check-label">
                    <input class="form-check-input" type="checkbox" checked disabled />
                    Remove all caches
                </label>
            </div>
            <div class="form-check my-1">
                <label class="form-check-label">
                    <input class="form-check-input" type="checkbox" checked disabled />
                    Don't overwrite custom css/js
                    <div><i><small>(just only classic ones)</small></i></div>
                </label>
            </div>
            <div class="form-check my-1">
                <label class="form-check-label">
                    <input class="form-check-input" type="checkbox" checked disabled />
                    Recompile css/js after update
                </label>
            </div>
            <div class="form-check my-1">
                <label class="form-check-label">
                    <input class="form-check-input" type="checkbox" checked disabled />
                    Don't overwrite table files
                    <div><i><small>(from uploads/)</small></i></div>
                </label>
            </div>
            <div class="form-check mt-1">
                <label class="form-check-label">
                    <input class="form-check-input" type="checkbox" checked disabled />
                    Create new PHP session
                </label>
            </div>
        </div>
        <div class="col-auto pt-2">
            <div class="h-100 border border-secondary"></div>
        </div>
        <div class="col">
            <div class="form-check">
                <label class="form-check-label" for="actions-rarely-update--mode--replace" data-toggle="tooltip" data-placement="left" title="Be careful!">
                    <input class="form-check-input" type="radio" name="replace" id="actions-rarely-update--mode--replace" value="1" />
                    <small>Replace project</small>
                </label>
            </div>
            <div class="form-check mt-1">
                <label class="form-check-label">
                    <input class="form-check-input" type="checkbox" checked disabled />
                    Remove all caches
                </label>
            </div>
            <div class="form-check my-1">
                <label class="form-check-label text-secondary">
                    <input class="form-check-input" type="checkbox" disabled />
                    Don't overwrite custom css/js
                    <div><i><small>(just only classic ones)</small></i></div>
                </label>
            </div>
            <div class="form-check my-1">
                <label class="form-check-label">
                    <input class="form-check-input" type="checkbox" checked disabled />
                    Recompile css/js after update
                </label>
            </div>
            <div class="form-check my-1">
                <label class="form-check-label text-secondary">
                    <input class="form-check-input" type="checkbox" disabled />
                    Don't overwrite table files
                    <div><i><small>(from uploads/)</small></i></div>
                </label>
            </div>
            <div class="form-check mt-1">
                <label class="form-check-label">
                    <input class="form-check-input" type="checkbox" checked disabled />
                    Create new PHP session
                </label>
            </div>
        </div>
    </div>

    <div class="response collapse"><hr /></div> <!-- response -->
</form>
