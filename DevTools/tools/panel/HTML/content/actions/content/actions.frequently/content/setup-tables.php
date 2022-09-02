<form action="setup-tables">
    <button type="submit" class="btn btn-success loader py-1">Setup tables</button>
    <div class="form-check mt-2">
        <label class="form-check-label">
            <input class="form-check-input" type="checkbox" checked disabled />
            Sync modules with DB
            <small><small class="d-block text-monospace">Looking for in outcomes/</small></small>
            <div class="form-check">
                <label class="form-check-label">
                    <input class="form-check-input" type="checkbox" checked disabled />
                    <small>Add non-existent language columns</small>
                </label>
            </div>
            <div class="my-1 d-flex flex-wrap">
                <div class="form-check form-check-inline">
                    <small>Unused language columns:</small>
                </div>
                <div class="d-inline nowrap">
                    <div class="form-check form-check-inline">
                        <label class="form-check-label d-flex" for="actions-frequently-tables--remove-lg--0">
                            <input class="form-check-input" type="radio" name="remove-lg" id="actions-frequently-tables--remove-lg--0" value="0" checked />
                            <small>Make them DEFAULT NULL</small>
                        </label>
                    </div>
                    <div class="form-check form-check-inline">
                        <label class="form-check-label d-flex" for="actions-frequently-tables--remove-lg--1" data-toggle="tooltip" data-placement="top" title="Be careful!">
                            <input class="form-check-input" type="radio" name="remove-lg" id="actions-frequently-tables--remove-lg--1" value="1" />
                            <small>Remove them</small>
                        </label>
                    </div>
                </div>
            </div>
        </label>
    </div>
    <div class="form-check mt-1">
        <label class="form-check-label">
            <input class="form-check-input" type="checkbox" checked disabled />
            Create and update validation tables
            <small class="d-block">
                Looking for in
                <?= implode(', ', Arsavinel\Arshwell\Func::arrayFlatten(json_decode(file_get_contents(Arsavinel\Arshwell\Folder::root() . 'composer.json'), true)['autoload'])) ?>
                classes
            </small>
        </label>
    </div>

    <div class="response collapse"><hr /></div> <!-- response -->
</form>
