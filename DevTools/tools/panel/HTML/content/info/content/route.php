<span class="text-muted mb-2 instance-of-panel"></span> <!-- the link we came from -->

<table class="table table-bordered table-dark">
    <?php
    if ($_REQUEST['request']['new']) { ?>
        <tr>
            <th>Session</th>
            <td>NEW</td>
        </tr>
    <?php } ?>
    <tr>
        <th>PHP</th>
        <td><?= $_REQUEST['request']['PHP'] ?></td>
    </tr>
    <tr>
        <th>Route</th>
        <td><?= $_REQUEST['request']['route'] ?></td>
    </tr>
    <tr>
        <th rowspan="2">URL</th>
        <?php
        if (rtrim(Arsavinel\Arshwell\Web::pattern($_REQUEST['request']['route'], $_REQUEST['request']['language']), '/') != trim($_REQUEST['request']['path'], '/')) { ?>
            <td><?= Arsavinel\Arshwell\Web::pattern($_REQUEST['request']['route'], $_REQUEST['request']['language']) ?></td>
        <?php } ?>
    </tr>
    <tr>
        <td><?= $_REQUEST['request']['path'] ?></td>
    </tr>
    <tr>
        <th>Folder</th>
        <td><?= Arsavinel\Arshwell\Web::folder($_REQUEST['request']['route']) ?></td>
    </tr>
    <?php
    if ($_REQUEST['request']['compiled']['css'] || $_REQUEST['request']['compiled']['js']['header'] || $_REQUEST['request']['compiled']['js']['footer']) { ?>
        <tr>
            <th>Compiled</th>
            <td>
                <table class="table table-bordered table-dark m-0">
                    <tr>
                        <?php
                        if ($_REQUEST['request']['compiled']['css']) { ?>
                            <td>CSS</td>
                        <?php } ?>
                        <?php
                        if ($_REQUEST['request']['compiled']['js']['header']) { ?>
                            <td>JS header</td>
                        <?php } ?>
                        <?php
                        if ($_REQUEST['request']['compiled']['js']['footer']) { ?>
                            <td>JS footer</td>
                        <?php } ?>
                    </tr>
                </table>
            </td>
        </tr>
    <?php } ?>
</table>
