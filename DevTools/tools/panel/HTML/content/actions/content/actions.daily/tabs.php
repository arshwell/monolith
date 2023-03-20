<a href="#actions-daily-recompile" data-toggle="pill"
class="nav-link btn-dark <?= (in_array(Arshwell\Monolith\Session::panel('box.tab.actions.daily'), [NULL, 'recompile']) ? 'active show' : '') ?>">
    Recompile existing css/js files
</a>
<a href="#actions-daily-crons" data-toggle="pill"
class="nav-link btn-dark d-flex justify-content-between align-items-center <?= (Arshwell\Monolith\Session::panel('box.tab.actions.daily') == 'crons' ? 'active show' : '') ?>">
    See all CRONs
    <?php $crons = count(Arshwell\Monolith\File::rFolder('crons', ['php'])); ?>
    <span class="rounded px-1 d-table text-center float-right btn-<?= ($crons ? 'info' : 'secondary') ?>">
        <?= $crons ?>
    </span>
</a>
<a href="#actions-daily-session" data-toggle="pill"
class="nav-link btn-dark <?= (Arshwell\Monolith\Session::panel('box.tab.actions.daily') == 'session' ? 'active show' : '') ?>">
    Empty app session
</a>
<a href="#actions-daily-unlinked" data-toggle="pill"
class="nav-link btn-dark <?= (Arshwell\Monolith\Session::panel('box.tab.actions.daily') == 'unlinked' ? 'active show' : '') ?>">
    Remove unlinked table files
</a>
