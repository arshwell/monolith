<li class="nav-item">
    <a class="nav-link <?= (in_array(Arsavinel\Arshwell\Session::panel('box.tab.resources'), [NULL, 'route']) ? 'active' : '') ?>" data-toggle="tab" href="#resources-route">
        Links for this route
    </a>
</li>
<li class="nav-item">
    <a class="nav-link <?= (Arsavinel\Arshwell\Session::panel('box.tab.resources') == 'site' ? 'active' : '') ?>" data-toggle="tab" href="#resources-site">
        Used by site
    </a>
</li>
