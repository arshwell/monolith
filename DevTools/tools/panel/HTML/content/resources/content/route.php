<span class="text-muted mb-2 instance-of-panel"></span> <!-- the link we came from -->

<?php
$links = array(
    'css'   => array(
        'web'   => Arsh\Core\Layout::mediaSCSS(Arsh\Core\Web::folder($_REQUEST['request']['route']), $_REQUEST['request']['pieces'], true)['files'],
        'mails' => Arsh\Core\Layout::mediaMailSCSS(Arsh\Core\Web::folder($_REQUEST['request']['route']), $_REQUEST['request']['pieces'], true)['files']
    ),
    'js'    => array(
        'header' => Arsh\Core\Layout::mediaJSHeader(Arsh\Core\Web::folder($_REQUEST['request']['route']), $_REQUEST['request']['pieces'])['files'],
        'footer' => Arsh\Core\Layout::mediaJSFooter(Arsh\Core\Web::folder($_REQUEST['request']['route']), $_REQUEST['request']['pieces'])['files']
    )
);

array_unshift($links['js']['header'], array(
    'name' => 'dynamic/'. Arsh\Core\Web::folder($_REQUEST['request']['route']) .'/web.js'
));

$time   = substr(str_shuffle("BCDFGHKLMNPQRSTVWXYZ"), 0, 4);
$asset  = Arsh\Core\ENV::root().'/uploads/design/';

$mediaLinks = Arsh\Core\Layout::mediaLinks($_REQUEST['request']['route'], $_REQUEST['request']['pieces']); ?>

<div class="row">
    <div class="col-12 col-lg-6">
        <div class="card bg-dark mb-2">
            <div class="card-header py-2">
                <a href="<?= $mediaLinks['urls']['css'] ?>" target="_blank" data-toggle="tooltip" data-placement="top" title="<?= $mediaLinks['paths']['css'] ?>">
                    CSS
                </a>
            </div>
            <div class="card-body py-1">
                <?php
                if ($links['css']['web']) {
                    foreach ($links['css']['web'] as $file) { ?>
                        <a href="<?= $asset.'dev/'.$file['name'] ?>?v=<?= $time ?>" target="_blank">
                            <?= $file['name'] ?>
                        </a><br>
                    <?php }
                } ?>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="card bg-dark mb-2">
            <div class="card-header py-2">
                CSS Mails
            </div>
            <div class="card-body py-1">
                <?php
                if ($links['css']['mails']) {
                    foreach ($links['css']['mails'] as $file) { ?>
                        <a href="<?= $asset.'dev/'.$file['name'] ?>?v=<?= $time ?>" target="_blank">
                            <?= $file['name'] ?>
                        </a><br>
                    <?php }
                } ?>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="card bg-dark mb-2">
            <div class="card-header py-2">
                <a href="<?= $mediaLinks['urls']['js']['header'] ?>" target="_blank" data-toggle="tooltip" data-placement="top" title="<?= $mediaLinks['paths']['js']['header'] ?>">
                    JS header
                </a>
            </div>
            <div class="card-body py-1">
                <?php
                if ($links['js']['header']) {
                    foreach ($links['js']['header'] as $file) { ?>
                        <a href="<?= $asset.'dev/'.$file['name'] ?>?v=<?= $time ?>" target="_blank">
                            <?= $file['name'] ?>
                        </a><br>
                    <?php }
                } ?>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="card bg-dark">
            <div class="card-header py-2">
                <a href="<?= $mediaLinks['urls']['js']['footer'] ?>" target="_blank" data-toggle="tooltip" data-placement="top" title="<?= $mediaLinks['paths']['js']['footer'] ?>">
                    JS footer
                </a>
            </div>
            <div class="card-body py-1">
                <?php
                if ($links['js']['footer']) {
                    foreach ($links['js']['footer'] as $file) { ?>
                        <a href="<?= $asset.'dev/'.$file['name'] ?>?v=<?= $time ?>" target="_blank">
                            <?= $file['name'] ?>
                        </a><br>
                    <?php }
                } ?>
            </div>
        </div>
    </div>
</div>
