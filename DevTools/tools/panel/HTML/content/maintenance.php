<?php

use Arsavinel\Arshwell\Session;
use Arsavinel\Arshwell\ENV;
use Arsavinel\Arshwell\Web;

// $sessions = Session::all(false, true); // without current session
$sessions = Session::all(true, true); // TODO: Delete it at the end

?>

<div class="row">
    <div class="col-12 col-md-6 col-xl-5">
        <div class="card bg-dark mb-2">
            <div class="card-header py-2">
                Setup maintenance
            </div>
            <div class="card-body py-2">
                <form action="setup-maintenance">
                    <button type="submit" class="btn btn-success loader py-1">Setup</button>
                    <hr class="my-2" />
                    <div class="row">
                        <div class="col-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" onclick="$('.maintenance--smart-configuration').collapse('hide');" name="type" id="maintenance--none" value="none" <?= (!ENV::maintenance('active') ? 'checked' : '') ?> />
                                <label class="form-check-label" for="maintenance--none">
                                    None
                                </label>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" onclick="$('.maintenance--smart-configuration').collapse('show');" name="type" id="maintenance--smart" value="smart" <?= (ENV::maintenance('active') && ENV::maintenance('smart') ? 'checked' : '') ?> />
                                <label class="form-check-label" for="maintenance--smart">
                                    SMART
                                </label>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" onclick="$('.maintenance--smart-configuration').collapse('hide');" name="type" id="maintenance--instant" value="instant" <?= (ENV::maintenance('active') && !ENV::maintenance('smart') ? 'checked' : '') ?> />
                                <label class="form-check-label" for="maintenance--instant">
                                    Instant
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="card bg-dark maintenance--smart-configuration collapse <?= (ENV::maintenance('active') && ENV::maintenance('smart') ? 'show' : '') ?> mt-3">
                        <div class="card-header py-2">
                            SMART configuration
                        </div>
                        <div class="card-body py-2">
                            <label class="mb-1">Visible history</label>
                            <select name="sessions" class="form-control">
                                <option value="0" selected>For all sessions</option>
                                <?php
                                foreach (array_keys($sessions) as $session_id) { ?>
                                    <option value="<?= $session_id ?>"><?= $session_id ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <div class="response collapse"><hr /></div> <!-- response -->
                </form>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-7">
        <div class="card bg-dark mb-2">
            <div class="card-header py-2">
                Routes accessed in real time
                <span class="maintenance--smart-configuration collapse <?= (ENV::maintenance('active') && ENV::maintenance('smart') ? 'show' : '') ?>">
                    <?php // all sessions with history ?>
                    (by all ~<?= count(array_filter(array_column(array_column($sessions, 'ArshWell'), 'history'))) ?> sessions)
                </span>
            </div>
            <div class="card-body py-0">
                <ul class="list-group list-group-flush maintenance--smart-configuration collapse <?= (ENV::maintenance('active') && ENV::maintenance('smart') ? 'show' : '') ?>">
                    <?php
                    foreach ($sessions as $session) {
                        foreach (array_reverse($session['ArshWell']['history']) as $index => $route) { ?>
                            <li type="button" class="list-group-item" data-toggle="collapse" data-target="#maintenance--history-<?= $index ?>" aria-expanded="false">
                                <div class="row">
                                    <div class="col-3 col-md-3 nowrap">
                                        <?= $route['request'] ?>
                                    </div>
                                    <div class="col-1 nowrap">
                                        <?php
                                        if ($route['instances'] > 1) { ?>
                                            (<?= $route['instances'] ?>)
                                        <?php } ?>
                                    </div>
                                    <div class="col-8 col-md-7">
                                        <?= $route['key'] ?>
                                    </div>
                                    <div class="collapse w-100" id="maintenance--history-<?= $index ?>">
                                        <table class="table table-sm table-bordered table-dark m-0 mt-2">
                                            <?php
                                            // Routes can be changed during development.
                                            if (Web::exists($route['key'])) { ?>
                                                <tr>
                                                    <th class="w-25">URL</th>
                                                    <td class="w-75 break-word"><?= Web::url($route['key'], $route['params'], $route['language'], $route['page'], $route['$_GET']) ?></td>
                                                </tr>
                                            <?php } ?>
                                            <tr>
                                                <th class="w-25">page</th>
                                                <td class="w-75"><?= $route['page'] ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </li>
                        <?php }
                    } ?>
                </ul>
                <small class="text-muted d-block my-2">They are shown only for SMART maintenance.</small>
            </div>
        </div>
    </div>
</div>
