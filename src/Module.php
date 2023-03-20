<?php

namespace Arshwell\Monolith;

use Arshwell\Monolith\Module\HTML\Piece;
use Arshwell\Monolith\Module\Backend;
use Arshwell\Monolith\ENV;
use Arshwell\Monolith\Web;
use Arshwell\Monolith\DB;

final class Module {

    /**
     * @param $back [array]
     * @param $request [array]
     *
     * @return [array|string]
    */
    static function backend (array $back, array $query, array $files = array()) {
        if (ENV::board('dev') && ENV::supervisor()) {
            // syntax validation
            foreach ($back as $key => $value) {
                $back[$key] = ("Arshwell\Monolith\Module\Syntax\Backend")::{$key}($value);
            }

            Backend::buildDB($back['DB'], $back['features'], $back['fields']);
        }

        DB::connect($back['DB']['conn']);

        if (!empty($query['ctn']) && isset($back['actions'][$query['ctn']])
        && method_exists("Arshwell\Monolith\Module\Request\Backend\Action\\". ucfirst($query['ctn']), Web::request())) {
            $callable = array("Arshwell\Monolith\Module\Request\Backend\Action\\". ucfirst($query['ctn']), Web::request());
        }
        else if (!empty($query['ftr']) && isset($back['features'][$query['ftr']])
        && ((!empty($query['id']) && is_numeric($query['id']) && is_int($query['id'] + 0)) || (!empty($query['ids']) && is_array($query['ids'])))
        && method_exists("Arshwell\Monolith\Module\Request\Backend\Feature\\". ucfirst($query['ftr']), Web::request())) {
            $callable = array("Arshwell\Monolith\Module\Request\Backend\Feature\\". ucfirst($query['ftr']), Web::request());
        }
        else if (Web::request() == 'GET') {
            $callable = array("Arshwell\Monolith\Module\Request\Backend\Action\Select", 'GET');
        }
        else {
            http_response_code(404);
            exit;
        }

        return call_user_func($callable, $back, $query, $files);
    }

    static function frontend (array $module, array $front): string {
        if (ENV::board('dev') && ENV::supervisor()) {
            // syntax validation
            foreach ($front as $key => $value) {
                $front[$key] = ("Arshwell\Monolith\Module\Syntax\Frontend")::{$key}($value);
            }
        }

        if (!empty($module['query']['ctn'])
        && method_exists("Arshwell\Monolith\Module\Request\Frontend\Action\\". ucfirst($module['query']['ctn']), Web::request())) {
            $callable = array("Arshwell\Monolith\Module\Request\Frontend\Action\\". ucfirst($module['query']['ctn']), Web::request());
        }
        else if (!empty($module['query']['ftr']) && !empty($module['query']['id']) && is_numeric($module['query']['id']) && is_int($module['query']['id'] + 0)
        && method_exists("Arshwell\Monolith\Module\Request\Frontend\Feature\\". ucfirst($module['query']['ftr']), Web::request())) {
            $callable = array("Arshwell\Monolith\Module\Request\Frontend\Feature\\". ucfirst($module['query']['ftr']), Web::request());
        }
        else if (Web::request() == 'GET') {
            $callable = array("Arshwell\Monolith\Module\Request\Frontend\Action\Select", 'GET');
        }

        ob_start(); ?>

            <div class="arshmodule">
                <?= call_user_func($callable, $module, $front) ?>

                <?= Piece::dialog() ?>
            </div>

        <?php
        return ob_get_clean();
    }
}
