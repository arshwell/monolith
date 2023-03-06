<?php

namespace ArshWell\Monolith\Module\Request\Frontend\Action;

use ArshWell\Monolith\Module\HTML\Piece;
use ArshWell\Monolith\URL;
use ArshWell\Monolith\Web;

final class Select {

    static function GET (array $module, array $front): string {
        if (isset($module['query']['sort'])) {
            unset($front['features']['order']);
        }

        ob_start(); ?>

            <div class="row">
                <?= Piece::actions($front['breadcrumbs'] ?? array(), $front['actions'] ?? array()) ?>
            </div>

            <form action="<?= URL::get(true, false) ?>" method="GET">
                <div class="row no-gutters">
                    <div class="col-sm-9 col-lg">
                        <?= Piece::search(
                            $module['query'],
                            array_diff_key($front['fields'], array_flip($module['back']['actions']['select']['columns']['private'] ?? array()))
                        ) ?>
                    </div>

                    <div class="col order-1 col-sm-3 order-sm-0 <?= (($module['back']['DB']['table'])::isTranslated() ? 'col-lg-auto' : 'd-lg-none') ?> order-lg-last card">
                        <div class="card-body text-sm-right py-3">
                            <?php
                            if (($module['back']['DB']['table'])::isTranslated()) {
                                echo Piece::languages((($module['back']['DB']['table'])::TRANSLATOR)::LANGUAGES, $module['query']['lg']);
                            } ?>
                        </div>
                    </div>

                    <div class="col-sm-9 col-lg">
                        <?= Piece::filter($module['query'], $front['fields'], $module['response']['options'] ?? array()) ?>
                    </div>

                    <div class="col order-2 col-sm-3 col-lg-auto card">
                        <div class="card-body text-right py-3">
                            <?= Piece::columns(
                                    array_diff_key($front['fields'], array_flip($module['back']['actions']['select']['columns']['private'] ?? array())),
                                    $module['query']['columns'] ?? array()
                                ) ?>
                        </div>
                    </div>
                </div>

                <?php
                if (Web::page() > 1) {
                    echo Piece::pagination(array(
                        'text'      => "records",
                        'count'     => $module['response']['count'],
                        'limit'     => $module['response']['limit'],
                        'buttons'   => array(
                            'xs' => 3,
                            'sm' => 4,
                            'md' => 5,
                            'lg' => 8,
                            'xl' => 12
                        ),
                        'icons' => array(
                            'first' => 'angle-double-left',
                            'left'  => 'angle-left',
                            'right' => 'angle-right',
                            'last'  => 'angle-double-right'
                        )
                    ));
                } ?>

                <div class="card border-bottom-0 rounded-0">
                	<div class="card-body pt-1 pb-0">
                        <?= Piece::thead(
                            $module['query'],
                            array_combine(array_keys($front['fields']), array_column($front['fields'], 'HTML')), // HTMLs
                            !empty($front['features']['order']) // show id table
                        ) ?>
                    </div>
                </div>
            </form>
            <div class="card border-top-0 rounded-0">
                <div class="card-body pt-0 pb-1">
                    <?= Piece::tbody(
                        $module['query'],
                        $module['response']['data'],
                        array_combine(array_keys($front['fields']), array_column($front['fields'], 'HTML')), // HTMLs
                        $front['features'],
                        !empty($front['features']['order']) // show id table
                    ) ?>
                </div>
            	<div class="card-footer">
                    <?= Piece::pagination(array(
                        'text'      => "records",
                        'count'     => $module['response']['count'],
                        'limit'     => $module['response']['limit'],
                        'buttons'   => array(
                            'xs' => 3,
                            'sm' => 4,
                            'md' => 5,
                            'lg' => 8,
                            'xl' => 12
                        ),
                        'icons' => array(
                            'first' => 'angle-double-left',
                            'left'  => 'angle-left',
                            'right' => 'angle-right',
                            'last'  => 'angle-double-right'
                        )
                    )) ?>
            	</div>
            </div>

        <?php
        return ob_get_clean();
    }
}
