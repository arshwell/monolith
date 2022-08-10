<form action="copy-directory">
    <button type="submit" class="btn btn-success loader py-1">Copy directory</button>
    <div class="row align-items-center text-muted my-2">
        <div class="col-3 col-lg-2 nowrap">Source:</div>
        <div class="col-9 col-lg-10">
            <input type="text" class="form-control" name="source" placeholder="What folder are you copying?" />
        </div>
        <div class="offset-3 offset-lg-2 col-9 col-lg-10">
            <small class="text-danger" form-error="source"></small>
        </div>
    </div>
    <div class="row align-items-center text-muted">
        <div class="col-3 col-lg-2 nowrap">Destination:</div>
        <div class="col-9 col-lg-10">
            <input type="text" class="form-control" name="destination" placeholder="Where do you copy it?" />
        </div>
        <div class="offset-3 offset-lg-2 col-9 col-lg-10">
            <small class="text-danger" form-error="destination"></small>
        </div>
    </div>
    <div class="row mb-1">
        <div class="offset-3 col-9 offset-lg-2 col-lg-10">
            <div class="form-check py-2">
                <label class="form-check-label" for="actions-frequently-directory--mkdir">
                    <input class="form-check-input" type="checkbox" name="mkdir" id="actions-frequently-directory--mkdir" value="1" />
                    Make destination dirs recursively, if necessary
                </label>
            </div>
            <div class="pb-2">
                <div class="form-check form-check-inline">
                    If destination exists:
                </div>
                <div class="d-inline nowrap">
                    <div class="form-check form-check-inline">
                        <label class="form-check-label" for="actions-frequently-directory--stop">
                            <input class="form-check-input" type="radio" name="behavior" id="actions-frequently-directory--stop" value="stop" checked />
                            Stop
                        </label>
                    </div>
                    <div class="form-check form-check-inline">
                        <label class="form-check-label" for="actions-frequently-directory--replace" data-toggle="tooltip" data-placement="top" title="Be careful!">
                            <input class="form-check-input" type="radio" name="behavior" id="actions-frequently-directory--replace" value="replace" />
                            Replace it
                        </label>
                    </div>
                    <div class="form-check form-check-inline">
                        <label class="form-check-label" for="actions-frequently-directory--merge" data-toggle="tooltip" data-placement="top" title="Be careful!">
                            <input class="form-check-input" type="radio" name="behavior" id="actions-frequently-directory--merge" value="merge" />
                            Merge them
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="response collapse"><hr /></div> <!-- response -->
</form>
