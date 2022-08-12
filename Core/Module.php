<?php

namespace Arsavinel\Arshwell;

use Arsavinel\Arshwell\Module\HTML\Piece;
use Arsavinel\Arshwell\Module\Backend;
use Arsavinel\Arshwell\ENV;
use Arsavinel\Arshwell\Web;
use Arsavinel\Arshwell\DB;

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
                $back[$key] = ("Arsavinel\Arshwell\Module\Syntax\Backend")::{$key}($value);
            }

            Backend::buildDB($back['DB'], $back['features'], $back['fields']);
        }

        DB::connect($back['DB']['conn']);

        if (!empty($query['ctn']) && isset($back['actions'][$query['ctn']])
        && method_exists("Arsavinel\Arshwell\Module\Request\Backend\Action\\". ucfirst($query['ctn']), Web::request())) {
            $callable = array("Arsavinel\Arshwell\Module\Request\Backend\Action\\". ucfirst($query['ctn']), Web::request());
        }
        else if (!empty($query['ftr']) && isset($back['features'][$query['ftr']])
        && ((!empty($query['id']) && is_numeric($query['id']) && is_int($query['id'] + 0)) || (!empty($query['ids']) && is_array($query['ids'])))
        && method_exists("Arsavinel\Arshwell\Module\Request\Backend\Feature\\". ucfirst($query['ftr']), Web::request())) {
            $callable = array("Arsavinel\Arshwell\Module\Request\Backend\Feature\\". ucfirst($query['ftr']), Web::request());
        }
        else if (Web::request() == 'GET') {
            $callable = array("Arsavinel\Arshwell\Module\Request\Backend\Action\Select", 'GET');
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
                $front[$key] = ("Arsavinel\Arshwell\Module\Syntax\Frontend")::{$key}($value);
            }
        }

        if (!empty($module['query']['ctn'])
        && method_exists("Arsavinel\Arshwell\Module\Request\Frontend\Action\\". ucfirst($module['query']['ctn']), Web::request())) {
            $callable = array("Arsavinel\Arshwell\Module\Request\Frontend\Action\\". ucfirst($module['query']['ctn']), Web::request());
        }
        else if (!empty($module['query']['ftr']) && !empty($module['query']['id']) && is_numeric($module['query']['id']) && is_int($module['query']['id'] + 0)
        && method_exists("Arsavinel\Arshwell\Module\Request\Frontend\Feature\\". ucfirst($module['query']['ftr']), Web::request())) {
            $callable = array("Arsavinel\Arshwell\Module\Request\Frontend\Feature\\". ucfirst($module['query']['ftr']), Web::request());
        }
        else if (Web::request() == 'GET') {
            $callable = array("Arsavinel\Arshwell\Module\Request\Frontend\Action\Select", 'GET');
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
