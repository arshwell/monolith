<a href="#actions-rarely-copy" data-toggle="pill"
class="nav-link btn-dark <?= (in_array(Arsh\Core\Session::panel('box.tab.actions.rarely'), [NULL, 'copy']) ? 'active show' : '') ?>">
    Copy project
</a>
<a href="#actions-rarely-update" data-toggle="pill"
class="nav-link btn-dark <?= (Arsh\Core\Session::panel('box.tab.actions.rarely') == 'update' ? 'active show' : '') ?>">
    Update project
</a>
