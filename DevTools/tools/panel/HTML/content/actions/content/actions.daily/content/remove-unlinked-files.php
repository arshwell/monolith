<form action="remove-unlinked-files">
    <button type="submit" class="btn btn-success loader py-1">Remove unlinked table files</button>
    <div class="form-check mt-2">
        <input class="form-check-input" type="checkbox" checked disabled />
        <label class="form-check-label">
            From <?= Arshwell\Monolith\StaticHandler::getEnvConfig()->getLocationPath('uploads') . 'files/' ?>
        </label>
    </div>
    <div class="form-check mb-2">
        <label class="form-check-label" for="actions-daily-unlinked--remove-lg">
            <input class="form-check-input" type="checkbox" name="remove-lg" id="actions-daily-unlinked--remove-lg" value="1" />
            Remove unnecessary language files
        </label>
    </div>

    <div class="response collapse"><hr /></div> <!-- response -->
</form>
