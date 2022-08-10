<li class="nav-item">
    <a class="nav-link <?= (in_array(Arsh\Core\Session::panel('box.tab.resources'), [NULL, 'route']) ? 'active' : '') ?>" data-toggle="tab" href="#resources-route">
        Links for this route
    </a>
</li>
<li class="nav-item">
    <a class="nav-link <?= (Arsh\Core\Session::panel('box.tab.resources') == 'site' ? 'active' : '') ?>" data-toggle="tab" href="#resources-site">
        Used by site
    </a>
</li>
