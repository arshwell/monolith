<ul class="list-group">
    <?php
    foreach (array_reverse(Arshwell\Monolith\Session::history()) as $index => $route) { ?>
        <li type="button" class="list-group-item" data-toggle="collapse" data-target="#history--<?= $index ?>" aria-expanded="false">
            <div class="row">
                <div class="col-2 col-md-1 nowrap">
                    <?= $route['request'] ?>
                </div>
                <div class="col-1 nowrap">
                    <?php
                    if ($route['instances'] > 1) { ?>
                        (<?= $route['instances'] ?>)
                    <?php } ?>
                </div>
                <div class="col-9 col-md-10">
                    <?= $route['key'] ?>
                </div>
            </div>
            <div class="row no-gutters">
                <div class="offset-3 offset-md-2 col">
                    <div class="collapse" id="history--<?= $index ?>">
                        <table class="table table-sm table-bordered table-dark m-0 mt-2">
                            <?php
                            // Routes can be changed during development.
                            if (Arshwell\Monolith\Web::exists($route['key'])) { ?>
                                <tr>
                                    <th class="w-25">URL</th>
                                    <td class="w-75 break-word"><?= Arshwell\Monolith\Web::url($route['key'], $route['params'], $route['language'], $route['page'], $route['$_GET']) ?></td>
                                </tr>
                            <?php }
                            if ($route['language']) { // only if has language ?>
                                <tr>
                                    <th class="w-25">language</th>
                                    <td class="w-75"><?= $route['language'] ?></td>
                                </tr>
                            <?php }
                            if ($route['page']) { // only if has pagination ?>
                                <tr>
                                    <th class="w-25">page</th>
                                    <td class="w-75"><?= $route['page'] ?></td>
                                </tr>
                            <?php } ?>
                        </table>
                    </div>
                </div>
            </div>
        </li>
    <?php } ?>
</ul>
