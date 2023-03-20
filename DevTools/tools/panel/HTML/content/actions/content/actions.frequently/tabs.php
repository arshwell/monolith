<a href="#actions-frequently-tables" data-toggle="pill"
class="nav-link btn-dark <?= (in_array(Arshwell\Monolith\Session::panel('box.tab.actions.frequently'), [NULL, 'tables']) ? 'active show' : '') ?>">
    Setup tables
</a>
<a href="#actions-frequently-backup" data-toggle="pill"
class="nav-link btn-dark <?= (Arshwell\Monolith\Session::panel('box.tab.actions.frequently') == 'backup' ? 'active show' : '') ?>">
    Backup data
</a>
<a href="#actions-frequently-download" data-toggle="pill"
class="nav-link btn-dark <?= (Arshwell\Monolith\Session::panel('box.tab.actions.frequently') == 'download' ? 'active show' : '') ?>">
    Download project
</a>
<a href="#actions-frequently-directory" data-toggle="pill"
class="nav-link btn-dark <?= (Arshwell\Monolith\Session::panel('box.tab.actions.frequently') == 'directory' ? 'active show' : '') ?>">
    Copy directory
</a>
