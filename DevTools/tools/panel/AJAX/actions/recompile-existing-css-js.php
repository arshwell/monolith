<?php

use Arshwell\Monolith\Table\TableValidation;
use Arshwell\Monolith\Session;
use Arshwell\Monolith\Layout;
use Arshwell\Monolith\Folder;
use Arshwell\Monolith\Cache;
use Arshwell\Monolith\Time;
use Arshwell\Monolith\ENV;
use Arshwell\Monolith\Web;

$form = TableValidation::run($_POST,
    array(
        'prev' => array(
            "optional|array"
        )
    ),
    array(
        'array' => "not array"
    )
);

if ($form->valid()) {
    try { // because could be thrown SCSS errors
        $info = array(
            'status'        => 'Existent CSS/JS files have been recompiled',
            'GET routes'    => count(Web::routes('GET')),
            'compiled'      => array(
                'css' => array(
                    'web'       => Layout::recompileSCSS(),
                    'mails'     => Layout::recompileMailSCSS()
                ),
                'js' => array(
                    'header'    => Layout::recompileJSHeader(),
                    'footer'    => Layout::recompileJSFooter()
                )
            ),
            'PHP' => Time::readableTime((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000)
        );

        $prev = $form->value('prev');

        if ($prev && $prev['pnl'] == "AJAX/actions/update-project") {
            // NOTE: create new session, because new Arshwell version
            // could expect different things.
            session_destroy();
            session_start();

            // NOTE: doing after recompile,
            // because it could take more than one request
            Session::set(ENV::url().ENV::db('conn.default.name'));
            Cache::delete(); // because new project version could have new logic

            $info['1.'] = '---';
            $info['PREV'] = '<a class="text-success" href="javascript:$(\'[href=&quot;#actions-rarely&quot;]\').click();$(\'[href=&quot;#actions-rarely-update&quot;]\').click();">Update project</a>';
            $info['2.'] = '---';
            $info['Extra'] = array(
                'Session destroyed',
                'Caches deleted'
            );
        }

        $form->info = $info;
    }
    catch (Exception $e) {
        $form->info = array(
            'status'    => "Error",
            'from'      => Folder::shorter($e->getFile()) .':'. $e->getLine(),
            'message'   => $e->getMessage()
        );
    }
}
else if ($form->expired()) {
    $form->info = array("Session expired. Reopen DevPanel!");
}

echo $form->json();
