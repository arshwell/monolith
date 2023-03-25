<table class="table table-bordered table-dark">
    <tr>
        <th>board.dev</th>
        <td>
            <?= Arshwell\Monolith\StaticHandler::getEnvConfig('development.debug') ? '<b>Yes</b>' : 'No' ?>
        </td>
    </tr>
    <tr>
        <th>IP</th>
        <td>
            <?= Arshwell\Monolith\StaticHandler::clientIP() ?>
            <span data-toggle="tooltip" data-placement="top" title="Supervisor key from config/development.json">
                (<?= array_search(Arshwell\Monolith\StaticHandler::clientIP(), Arshwell\Monolith\StaticHandler::getEnvConfig('development.ips')) ?>)
            </span>
        </td>
    </tr>
    <tr data-toggle="collapse" href="#routes-count-all,#routes-count-request" role="button">
        <th class="va-top" data-toggle="tooltip" data-placement="left" title="Toggle requests">
            Routes
        </th>
        <td>
            <div class="collapse show fade" id="routes-count-all">
                <?= count(Arshwell\Monolith\Web::routes()) ?>
            </div>
            <table class="table table-bordered table-dark m-0 collapse fade" id="routes-count-request">
                <tr><td colspan="2" class="border-0 p-0">
                    <sup class="text-muted">They could be duplicated. Because a route can accept more requests.</sup>
                </td></tr>
                <?php
                foreach (array_unique(call_user_func_array('array_merge', array_column(Arshwell\Monolith\Web::routes(), 1))) as $request) { ?>
                    <tr>
                        <th><?= $request ?></th>
                        <td><?= count(Arshwell\Monolith\Web::routes($request)) ?></td>
                    </tr>
                <?php } ?>
            </table>
        </td>
    </tr>
</table>
