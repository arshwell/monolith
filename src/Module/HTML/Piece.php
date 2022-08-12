<?php

namespace Arsavinel\Arshwell\Module\HTML;

use Arsavinel\Arshwell\Table\TableSegment;
use Arsavinel\Arshwell\Table\TableColumn;
use Arsavinel\Arshwell\Table\TableField;
use Arsavinel\Arshwell\Table\TableFiles;
use Arsavinel\Arshwell\Table;
use Arsavinel\Arshwell\Text;
use Arsavinel\Arshwell\File;
use Arsavinel\Arshwell\Func;
use Arsavinel\Arshwell\URL;
use Arsavinel\Arshwell\Web;

final class Piece {

    static function actions (array $breadcrumbs, array $actions = array()): string {
        ob_start(); ?>

            <div class="card border-left-0 border-top-0 border-right-0 rounded-0 w-100 mb-3">
                <div class="card-body py-2">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <ul class="breadcrumb bg-transparent align-items-center p-0 py-1 m-0">
                                <?php
                                foreach ($breadcrumbs as $breadcrumb) { ?>
                                    <li class="breadcrumb-item align-items-center"><?= $breadcrumb ?></li>
                                <?php  } ?>
                            </ul>
                        </div>
                        <div class="col-auto ml-auto">
                            <?php
                            foreach ($actions as $key => $action) { ?>
                                <span class="ml-1">
                                    <?= self::action($key, $action) ?>
                                </span>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>

        <?php
        return ob_get_clean();
    }

    /**
     * (closure|array) $action
    */
    static function action (string $key, $action): string {
        if (!is_string($action) && is_callable($action)) {
            $action = $action($key);
        }

        $action['HTML'] = array_replace_recursive(
            array(
                'icon'      => NULL,
                'text'      => '',
                'href'      => URL::get(),
                'type'      => 'link',
                'class'     => '',
                'disabled'  => false,
                'hidden'    => false,
                'values'    => array()
            ),
            $action['HTML']
        );

        array_walk_recursive($action, function (&$value) use ($key) {
            if (!is_string($value) && is_callable($value)) {
                $value = $value($key);
            }
        });

        $action['HTML']['href'] = URL::get(true, false, $action['HTML']['href']) .'?ctn='. $key;

        return array(
            'Arsavinel\Arshwell\Module\HTML\Action',
            $action['HTML']['type']
        )($key, $action);
    }

    static function search (array $query, array $fields): string {
        ob_start(); ?>

            <div class="arshmodule-addon-search card h-100">
                <div class="card-body py-3">
                    <?php
                    if (isset($query['search'])) {
                        $query['search'] = array_map(function ($array) {
                            return array_unique($array);
                        }, $query['search']);

                        foreach ($query['search'] as $key => $values) {
                            foreach ($values as $value) { ?>
                                <input type="hidden" name="search[<?= $key ?>][]" value="<?= $value ?>" />
                            <?php }
                        }
                    } ?>

                    <div class="row">
                        <div class="col-sm mb-1 mb-sm-0">
                            <input type="text" class="form-control h-100" placeholder="Caută...">
                        </div>
                        <div class="col">
                            <select class="custom-select h-100">
                                <?php
                                foreach ($fields as $key => $field) {
                                    switch ($field['HTML']['type']) {
                                        case 'text':
                                        case 'textarea':
                                        case 'number': { ?>
                                            <option value="<?= $key ?>">
                                                în <?= $field['HTML']['label'] ?>
                                            </option>
                                            <?php
                                            break;
                                        }
                                    }
                                } ?>
                            </select>
                        </div>
                        <div class="col-auto text-right">
                            <button class="btn btn-danger" title="Caută">
                                <i class="fa fa-fw fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php
                if (isset($query['search']) || isset($query['filter'])) { ?>
                    <div class="card-footer">
                        <?php
                        if (!isset($query['search'])) {
                            echo "<i>Nicio căutare adăugată.</i>";
                        }
                        else {
                            foreach ($query['search'] as $field => $values) {
                                foreach ($values as $value) { ?>
                                    <span class="nowrap mr-3" data-field="<?= $field ?>" data-value="<?= $value ?>">
                                        <b><?= $fields[$field]['HTML']['label'] ?>:</b>
                                        <?= ($value ?: '<span class="badge badge-danger">Gol</span>') ?>
                                        <i type="button" class="fa fa-fw fa-times-circle text-danger"></i>
                                    </span>
                                <?php }
                            }
                        } ?>
                    </div>
                <?php } ?>
            </div>

        <?php
        return ob_get_clean();
    }

    static function filter (array $query, array $fields, array $options = array()): string {
        ob_start(); ?>

        <div class="arshmodule-addon-filter card h-100">
            <div class="card-body py-3">
                <?php
                if (array_intersect(array('select', 'radio'), array_column(array_column($fields, 'HTML'), 'type'))) { ?>
                    <?php
                    if (isset($query['filter'])) {
                        $query['filter'] = array_map(function ($array) {
                            return array_unique($array);
                        }, $query['filter']);

                        foreach ($query['filter'] as $field => $values) {
                            foreach ($values as $value) { ?>
                                <input type="hidden" name="filter[<?= $field ?>][]" value="<?= $value ?>" />
                            <?php }
                        }
                    } ?>

                    <div class="row">
                        <div class="col-sm mb-1 mb-sm-0">
                            <select class="custom-select h-100" title="Filtrează după">
                                <option selected hidden>Filtrează după</option>
                                <?php
                                foreach ($fields as $key => $field) {
                                    if (in_array($field['HTML']['type'], array('select', 'radio'))) { ?>
                                        <option value="<?= $key ?>">
                                            <?= $field['HTML']['label'] ?>
                                        </option>
                                    <?php }
                                } ?>
                            </select>
                        </div>
                        <div class="col">
                            <select class="custom-select h-100" disabled></select>
                            <?php
                            foreach ($fields as $key => $field) {
                                if (in_array($field['HTML']['type'], array('select', 'radio'))) { ?>
                                    <select class="custom-select h-100 d-none" data-key="<?= $key ?>">
                                        <?php
                                        foreach (($options[$key] ?? $field['HTML']['values'] ?? array()) as $index => $value) { ?>
                                            <option value="<?= $index ?>"><?= $value ?></option>
                                        <?php } ?>
                                    </select>
                                <?php }
                            } ?>
                        </div>
                        <div class="col-auto text-right">
                            <button type="submit" class="btn btn-danger" title="Filtrează">
                                <i class="fa fa-fw fa-search"></i>
                            </button>
                        </div>
                    </div>
                <?php } ?>
            </div>
            <?php
            if (isset($query['search']) || isset($query['filter'])) { ?>
                <div class="card-footer">
                    <?php
                    if (!isset($query['filter'])) {
                        echo "<i>Niciun filtru adăugat.</i>";
                    }
                    else {
                        foreach ($query['filter'] as $key => $values) {
                            foreach ($values as $value) { ?>
                                <span class="nowrap mr-3" data-field="<?= $key ?>" data-value="<?= $value ?>">
                                    <b><?= $fields[$key]['HTML']['label'] ?>:</b>
                                    <?= (($options[$key][$value] ?? $fields[$key]['HTML']['values'][$value]) ?: '<span class="badge badge-danger">Gol</span>') ?>
                                    <i type="button" class="fa fa-fw fa-times-circle text-danger"></i>
                                </span>
                            <?php }
                        }
                    } ?>
                </div>
            <?php } ?>
        </div>

        <?php
        return ob_get_clean();
    }

    static function columns (array $fields, array $visible = array()): string {
        ob_start(); ?>

            <div class="arshmodule-addon-columns">
                <div class="dropdown btn pl-0 pr-0 pb-0 ml-0 mr-0 mb-0">
                    <button class="btn btn-sm <?= (count($fields) == count($visible) ? 'btn-dark' : 'btn-secondary') ?> dropdown-toggle"
                    type="button" title="Câmpuri vizibile" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-offset="0,5">
                        Câmpuri
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <?php
                        foreach ($fields as $key => $field) { ?>
                            <div class="dropdown-item">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="columns[]" <?= (empty($visible) || in_array($key, $visible) ? 'checked' : '') ?> value="<?= $key ?>" id="arshmodule-addon-column-<?= $key ?>">
                                    <label class="form-check-label w-100" for="arshmodule-addon-column-<?= $key ?>">
                                        <?= $field['HTML']['label'] ?>
                                    </label>
                                </div>
                            </div>
                        <?php } ?>
                        <div class="dropdown-divider"></div>
                        <div class="dropdown-item">
                            <button type="submit" class="btn btn-sm btn-primary">
                                Afișează
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        <?php
        return ob_get_clean();
    }

    static function languages (array $languages, string $lg, bool $submit = true): string {
        ob_start(); ?>

            <div class="arshmodule-addon-language <?= ($submit ? 'submit' : '') ?>">
                <?php
                if ($submit) { ?>
                    <input type="hidden" name="lg" value="<?= $lg ?>" />
                <?php } ?>

                <div class="dropdown btn px-0 pb-0 mx-0 mb-0">
                    <button class="btn btn-sm dropdown-toggle text-light" type="button" data-lg="<?= $lg ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-offset="0,5">
                        <?= strtoupper($lg) ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right py-0">
                        <?php
                        foreach ($languages as $i => $language) { ?>
                            <button class="dropdown-item btn-sm text-light" type="button" data-lg="<?= $language ?>">
                                <?= strtoupper($language) ?>
                            </button>
                        <?php } ?>
                    </div>
                </div>
            </div>

        <?php
        return ob_get_clean();
    }

    static function thead (array $query, array $HTMLs, bool $show_id_table = false): string {
        ob_start(); ?>

            <div class="arshmodule-html arshmodule-html-piece arshmodule-html-piece-thead">
                <div class="table-responsive">
                    <?php
                    if (!empty($query['sort'])) {
                        foreach ($query['sort'] as $key => $value) { ?>
                            <input type="hidden" name="sort[<?= $key ?>]" value="<?= $value ?>" />
                        <?php }
                    } ?>
                    <table class="table table-striped mb-1">
                        <thead>
                            <tr>
                                <?php
                                if ($show_id_table) { ?>
                                    <th class="th-id-table">ID</th>
                                <?php }

                                foreach ($query['columns'] as $key) { ?>
                                    <td>
                                        <?php
                                        switch ($HTMLs[$key]['type']) {
                                            case 'image':
                                            case 'images':
                                            case 'doc':
                                            case 'docs':
                                            case 'icon': {
                                                echo $HTMLs[$key]['label'];
                                                break;
                                            }
                                            default: { ?>
                                                <span type="button" data-key="<?= $key ?>" title="Sortează"
                                                data-sort="<?= (!isset($query['sort'][$key]) || $query['sort'][$key] == 'd' ? 'a' : 'd') ?>">
                                                    <?= $HTMLs[$key]['label'] ?>
                                                    <i class="fa fa-fw fa-sort"></i>
                                                </span>

                                                <?php
                                                if (isset($query['sort'][$key])) { ?>
                                                    <i type="button" class="fa fa-fw fa-times-circle text-danger" data-key="<?= $key ?>"></i>

                                                    <?php
                                                    if ($query['sort'][$key] == 'a') { ?>
                                                        <i class="fa fa-fw fa-sort-up text-danger"></i>
                                                    <?php }
                                                    else if ($query['sort'][$key] == 'd') { ?>
                                                        <i class="fa fa-fw fa-sort-down text-danger"></i>
                                                    <?php }
                                                }
                                                break;
                                            }
                                        } ?>
                                    </td>
                                <?php } ?>
                                <td class="text-right border-top-0">
                                    Acțiuni
                                </td>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

        <?php
        return ob_get_clean();
    }

    static function tbody (array $query, array $data, array $HTMLs, array $features, bool $show_id_table = false): string {
        ob_start(); ?>
            <div class="arshmodule-html arshmodule-html-piece arshmodule-html-piece-tbody">
                <div class="table-responsive">
                    <table class="table table-striped mb-1">
                        <tbody>
                            <?php
                            foreach ($data as $id_table => $row) { ?>
                                <tr>
                                    <?php
                                    if ($show_id_table) { ?>
                                        <th class="th-id-table"><?= $id_table ?></th>
                                    <?php }

                                    foreach ($query['columns'] as $key) {
                                        if (isset($HTMLs[$key])) {
                                            $HTML = $HTMLs[$key];

                                            $lg = NULL;
                                            $value = $row[$key];

                                            if (is_object($value)) {
                                                $lg = ($row[$key])->isTranslated() ? ($query['lg'] ?? NULL) : NULL;

                                                switch (get_class($value)) {
                                                    case TableField::class:
                                                    case TableColumn::class: {
                                                        $value = $value->value($lg);
                                                    }
                                                }
                                            }

                                            array_walk_recursive($HTML, function (&$h) use ($value) {
                                                if (!is_string($h) && is_callable($h)) {
                                                    $h = $h($value);
                                                }
                                            }); ?>

                                            <td>
                                                <?php
                                                switch ($HTML['type']) {
                                                    case 'image': {
                                                        if ($value->urls()) {
                                                            $smallest = $value->smallest($lg);
                                                            $biggest  = $value->biggest($lg); ?>

                                                            <a href="<?= $biggest ?>"
                                                            data-caption="<?= basename($biggest) ?>" data-fancybox="<?= $id_table ?>-<?= $key ?>"
                                                            data-thumb="<?= $smallest ?>"
                                                            data-protect="true">
                                                                <div class="arshmodule-table-image">
                                                                    <img src="<?= $smallest ?>" />
                                                                </div>
                                                            </a>
                                                        <?php }
                                                        break;
                                                    }
                                                    case 'images': {
                                                        if ($value->urls()) {
                                                            $smallest = $value->smallest($lg);
                                                            $biggest  = $value->biggest($lg); ?>

                                                            <a href="<?= $biggest[0] ?>"
                                                            data-caption="<?= basename($biggest[0]) ?>" data-fancybox="<?= $id_table ?>-<?= $key ?>"
                                                            data-thumb="<?= $smallest[0] ?>"
                                                            data-protect="true">
                                                                <div class="arshmodule-table-image">
                                                                    <img src="<?= $smallest[0] ?>" />
                                                                </div>
                                                            </a>

                                                            <?php
                                                            for ($i=1; $i<count($smallest); $i++) { ?>
                                                                <a href="<?= $biggest[$i] ?>"
                                                                data-caption="<?= basename($biggest[$i]) ?>" data-fancybox="<?= $id_table ?>-<?= $key ?>"
                                                                data-thumb="<?= $smallest[$i] ?>"
                                                                data-protect="true"
                                                                class="d-none">
                                                                    <img src="<?= $smallest[$i] ?>" />
                                                                </a>
                                                            <?php }
                                                        }
                                                        break;
                                                    }
                                                    case 'doc': {
                                                        if ($value->url()) { ?>
                                                            <a href="<?= $value->url() ?>" target="_blank"
                                                            class="btn badge btn-outline-info px-2" title="<?= basename($value->url()) ?>">
                                                                <?= strtoupper(File::extension($value->url())) ?>
                                                            </a>
                                                        <?php }
                                                        break;
                                                    }
                                                    case 'video': {
                                                        if ($value->url()) { ?>
                                                            <a href="<?= $value->url() ?>" target="_blank">
                                                                <video preload="metadata">
                                                                    <source src="<?= $value->url() ?>" />
                                                                    Browser-ul tău nu suportă HTML5 video.
                                                                </video>
                                                            </a>
                                                        <?php }
                                                        break;
                                                    }
                                                    case 'icon': { ?>
                                                        <i class="<?= $value ?> fa-fw fa-2x"></i>
                                                        <?php
                                                        break;
                                                    }
                                                    case 'date': {
                                                        echo ($value ? date('d-m-Y', $value) : '');
                                                        break;
                                                    }
                                                    case 'checkbox': {
                                                        echo ($value ? 'Da' : 'Nu');
                                                        break;
                                                    }
                                                    case 'select': {
                                                        if (!empty($HTML['multiple'])) {
                                                            echo implode(', ', $value);
                                                        }
                                                        else {
                                                            echo ($HTML['values'][$value] ?? $value);
                                                        }
                                                        break;
                                                    }
                                                    default: { ?>
                                                        <span class="text">
                                                            <?php
                                                            if (isset($HTML['preview'])) {
                                                                echo $HTML['preview'];
                                                            }
                                                            else {
                                                                echo ($value ? Text::chars(Text::removeAllTags($value), 150) : '');
                                                            } ?>
                                                        </span>
                                                        <?php
                                                        break;
                                                    }
                                                } ?>
                                            </td>
                                        <?php }
                                    } ?>
                                    <td class="arshmodule-html-features align-middle text-right nowrap">
                                        <?= self::features($features, $id_table) ?: '-' ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                    <?php
                    if (empty($data)) { ?>
                        <small class="text-muted">Nicio înregistrare</small>
                    <?php } ?>
                </div>
            </div>

        <?php
        return ob_get_clean();
    }

    static function features (array $features, int $id_table): string {
        ob_start();

            foreach ($features as $key => $feature) {
                echo self::feature($key, $feature, $id_table);
            }

        return ob_get_clean();
    }

    /**
     * (closure|array) $feature
    */
    static function feature (string $key, $feature, int $id_table): string {
        if (!is_string($feature) && is_callable($feature)) {
            $feature = $feature($key, $id_table);
        }

        $feature['HTML'] = array_replace_recursive(
            array(
                'icon'      => NULL,
                'text'      => '',
                'href'      => URL::get(true, false), // not getting query for avoiding conflicts
                'type'      => 'link',
                'class'     => '',
                'disabled'  => false,
                'hidden'    => false,
                'values'    => array()
            ),
            $feature['HTML']
        );

        array_walk_recursive($feature, function (&$value) use ($key, $id_table) {
            if (!is_string($value) && is_callable($value)) {
                $value = $value($key, $id_table);
            }
        });

        foreach ($feature as $category => $attributes) {
            foreach ($attributes as $attr => $value) {
                $feature[$category][$attr] = ("Arsavinel\Arshwell\Module\Syntax\Frontend\Feature\\{$category}")::{$attr}($value);
            }
        }

        $query = parse_url($feature['HTML']['href'], PHP_URL_QUERY);
        $feature['HTML']['href'] = URL::get(true, false, $feature['HTML']['href']) .'?'.($query ? $query.'&' : '').'ftr='. $key .'&id=' . $id_table;

        return array(
            'Arsavinel\Arshwell\Module\HTML\Feature',
            $feature['HTML']['type']
        )($key, $feature, $id_table);
    }

    static function fields (string $table, array $fields, array $data = NULL, array $translated = array()): string {
        ob_start(); ?>

            <div class="row">
                <?php
                foreach ($fields as $key => $field) {
                    echo self::field(
                        $key,
                        $field,
                        $data[$key] ?? NULL, // TableFile | TableField | TableColumn | NULL
                        (in_array($key, $translated) ? (($table)::TRANSLATOR)::LANGUAGES : NULL)
                    );
                } ?>
            </div>

        <?php
        return ob_get_clean();
    }

    static function field (string $key, array $field, TableSegment $segment = NULL, array $languages = NULL): string {
        // run $field if it is a closure
        if (!is_string($field) && is_callable($field)) {
            $field = $field(
                $segment ? $segment->key() : NULL,
                $segment ? $segment->id() : NULL,
                $segment ? $segment->class() : NULL
            );
        }

        $field['HTML'] = array_replace_recursive(
            array(
                'wrappers' => array(
                    "col-12 col-sm-4 col-md-3 col-xl-2",
                    "col-12 col-sm-8 col-md-9 col-xl-10"
                ),
                'icon'          => NULL,
                'label'         => NULL,
                'type'          => 'text',
                'notes'         => array(),
                'class'         => '',
                'disabled'      => false,
                'readonly'      => false,
                'hidden'        => false,
                'checked'       => false,
                'placeholder'   => '',
                'multiple'      => false,
                'value'         => NULL,
                'values'        => array(),
                'overwrite'     => true
            ),
            $field['HTML']
        );

        // run subkeys which are closures
        array_walk_recursive($field, function (&$value) use ($segment) {
            if (!is_string($value) && is_callable($value)) {
                $value = $value(
                    $segment ? $segment->key() : NULL,
                    $segment ? $segment->id() : NULL,
                    $segment ? $segment->class() : NULL
                );
            }
        });

        list($class_1, $class_2) = array_values($field['HTML']['wrappers']);

        if (!$languages) {
            $languages = array(NULL);
        }

        ob_start();

            foreach ($languages as $i => $lg) {
                if (empty($field['HTML']['id'])) {
                    $field['HTML']['id'] = ("data-".Text::slug($key));
                } ?>

                <div data-key="<?= $field['HTML']['id'] ?>" <?= (count($languages) > 1 ? 'data-lg="'.$lg .'"' : '') ?>
                class="<?= $class_1 ?> text-muted py-sm-2" <?= ($field['HTML']['hidden'] || $i > 0 ? 'style="display: none;"' : '') ?>>
                    <label <?= ($field['HTML']['label'] ? 'title="'.$field['HTML']['label'].'"' : '') ?>
                    for="<?= $field['HTML']['id'] ?><?= $lg ? "-$lg" : '' ?>">
                        <?php
                        if ($field['HTML']['icon']) {
                            switch ($field['HTML']['icon']['style'] ?? NULL) {
                                case NULL:
                                case 'solid': {
                                    $fa_class = 'fas';
                                    break;
                                }
                                case 'regular': {
                                    $fa_class = 'far';
                                    break;
                                }
                                case 'brand': {
                                    $fa_class = 'fab';
                                    break;
                                }
                            } ?>
                            <i class="<?= $fa_class ?> fa-fw fa-<?= $field['HTML']['icon']['name'] ?? $field['HTML']['icon'] ?>"></i>
                        <?php }
                        if ($field['HTML']['label']) {
                            echo $field['HTML']['label'];
                        }
                        if (!empty($field['HTML']['required'])) { ?>
                            <span class="text-danger">*</span>
                        <?php } ?>
                    </label>
                </div>
                <div data-key="<?= $field['HTML']['id'] ?>" <?= (count($languages) > 1 ? 'data-lg="'.$lg .'"' : '') ?>
                class="<?= $class_2 ?> py-sm-2" <?= ($field['HTML']['hidden'] || $i > 0 ? 'style="display: none;"' : '') ?>>
                    <?php
                    if ($segment && (!isset($field['HTML']['value']) || $field['HTML']['overwrite'])) {
                        switch ($field['HTML']['type']) {
                            case 'doc':
                            case 'docs':
                            case 'video':
                            case 'image':
                            case 'images': {
                                $field['HTML']['value'] = $segment;
                                break;
                            }
                            case 'select': {
                                if (empty($field['HTML']['multiple'])) {
                                    $field['HTML']['value'] = (array)$segment->value($lg);
                                }
                                else {
                                    $field['HTML']['value'] = array_keys($segment->value($lg));
                                }
                                break;
                            }
                            case 'checkbox': {
                                $field['HTML']['checked'] = ($field['HTML']['value'] == $segment->value($lg));
                                break;
                            }
                            default: {
                                $field['HTML']['value'] = $segment->value($lg);
                                break;
                            }
                        }
                    }

                    if (empty($field['HTML']['name'])) {
                        $field['HTML']['name'] = "data[$key]";
                    }

                    echo array(
                        'Arsavinel\Arshwell\Module\HTML\Field',
                        $field['HTML']['type']
                    )($field, $lg); ?>
                </div>
            <?php }

        return ob_get_clean();
    }

    static function saver (array $afters, bool $preservation = false): string {
        ob_start(); ?>

            <div class="arshmodule-html asrhmodule-html-piece arshmodule-html-piece-saver">
                <div class="card mb-3">
                    <h6 class="card-header">Salvare</h6>
                    <div class="card-body pt-3">
                        <?php
                        if ($afters) { ?>
                            <small>După salvare:</small>
                        <?php } ?>
                        <div class="row align-items-center">
                            <?php
                            if ($afters) { ?>
                                <div class="col-sm-auto col-lg-12">
                                    <select class="custom-select" name="after" title="După salvare...">
                                        <?php
                                        foreach (array_unique($afters) as $key) {
                                            if (!is_int($key)) {
                                                switch ($key) {
                                                    case 'select': { ?>
                                                        <option value="<?= $key ?>">Mergi la vizualizare</option>
                                                        <?php
                                                        break;
                                                    }
                                                    case 'update': { ?>
                                                        <option value="<?= $key ?>" selected>Editează această înregistrare</option>
                                                        <?php
                                                        break;
                                                    }
                                                    case 'insert': { ?>
                                                        <option value="<?= $key ?>">Adaugă o nouă înregistrare</option>
                                                        <?php
                                                        break;
                                                    }
                                                }
                                            }
                                        } ?>
                                    </select>
                                </div>
                            <?php }
                            if ($preservation) { ?>
                                <div class="col-sm-auto my-1 col-lg-12 mr-auto">
                                    <div class="custom-control custom-checkbox pl-0">
                                        <input
                                        type="checkbox"
                                        class="custom-control-input"
                                        id="arshmodule-form-preservation"
                                        name="preservation"
                                        value="1"
                                        form-valid-update="false"
                                        />
                                        <label class="custom-control-label d-flex" style="padding-top: 2px;" for="arshmodule-form-preservation">
                                            Păstrează câmpurile după salvare
                                        </label>
                                    </div>
                                </div>
                            <?php } ?>
                            <div class="col-sm-auto my-1 col-lg-12 ml-auto text-right">
                                <input type="submit" class="btn btn-sm" value="Salvează" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php
        return ob_get_clean();
    }

    static function dialog (): string {
        ob_start(); ?>

            <div class="modal fade arshmodule-html arshmodule-html-piece arshmodule-html-piece-dialog" tabindex="-1">
                <div class="modal-dialog modal-sm modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content border-0 bg-dark text-light">
                        <div class="modal-header border-secondary">
                            <h6 class="modal-title"></h6>
                            <button type="button" class="close text-light" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="arshmodule-modal-info"></div>
                            <div class="arshmodule-modal-errors">
                                Rezolvă mențiunile:
                                <ul class="list-group list-group-flush mt-1 text-break">
                                    <li class="list-group-item list-group-item-warning d-none"></li>
                                </ul>
                            </div>
                            <div class="arshmodule-modal-bug">
                                Au apărut niște erori neașteptate.<br>Încearcă pe rând, în ordine, următorii pași:
                                <ul class="list-group mt-1">
                                    <li class="media d-flex list-group-item list-group-item-danger">
                                        1.
                                        <div class="media-body ml-1">
                                            <b>Reîncarcă pagina</b> și completează din nou. Merge?
                                        </div>
                                    </li>
                                    <li class="media d-flex list-group-item list-group-item-danger">
                                        2.
                                        <div class="media-body ml-1">
                                            Sunt șanse să editeze și altcineva același conținut în acest moment?
                                        </div>
                                    </li>
                                    <li class="media d-flex list-group-item list-group-item-danger">
                                        3.
                                        <div class="media-body ml-1">
                                            <u>Tot nu merge?</u> Anunță dezvoltatorul site-ului.
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="modal-footer border-secondary text-light">
                            <small>În limbile: <span class="arshmodule-modal-languages"></span></small>
                        </div>
                    </div>
                </div>
            </div>

        <?php
        return ob_get_clean();
    }

    static function pagination (array $config): string {
        $links = call_user_func(function () use ($config): array {
            $config['icons'] = array(
                'first' => '<i class="fa fa-'. $config['icons']['first'] . '"></i>',
                'left'  => '<i class="fa fa-'. $config['icons']['left'] . '"></i>',
                'right' => '<i class="fa fa-'. $config['icons']['right'] . '"></i>',
                'last'  => '<i class="fa fa-'. $config['icons']['last'] . '"></i>'
            );

            $page = Web::page();
            $nr_of_pages = ceil($config['count'] / $config['visible']); // round up

            $links = array();

            if ($config['count'] > $config['visible']) {
                $range = function (int $nr_of_links) use ($page, $nr_of_pages) {
                    $nr_of_links = min($nr_of_links, $nr_of_pages);

                    $a = 1;
                    $z = $nr_of_links;
                    $ceil   = ceil($nr_of_links / 2);  // round up
                    $floor  = floor($nr_of_links / 2); // round down

                    if ($page > $ceil) {
                        $a = $page - $floor;
                        $z = $page + $floor - 1;
                    }
                    if ($page > ($nr_of_pages - $ceil)) {
                        $a = max(1, $nr_of_pages - $nr_of_links);
                        $z = $nr_of_pages;
                    }

                    return array($a, $z);
                };

                $ranges = array();
                foreach ($config['buttons'] as $resolution => $max) {
                    $r = $range($max);
                    foreach (range($r[0], $r[1]) as $v) {
                        $ranges[$v][] = $resolution;
                    }
                }
                ksort($ranges);

                if ($page > 6) {
                    $links[] = array(
                        'url'       => Web::url(Web::key(), Web::params(), Web::language(), 1, $_GET),
                        'title'     => $config['icons']['first'],
                        'active'    => false
                    );
                }
                if ($page > 1) {
                    $links[] = array(
                        'url'       => Web::url(Web::key(), Web::params(), Web::language(), $page - 1, $_GET),
                        'title'     => $config['icons']['left'],
                        'active'    => false
                    );
                }

                foreach ($ranges as $p => $resolutions) {
                    $links[] = array(
                        'url'       => Web::url(Web::key(), Web::params(), Web::language(), $p, $_GET),
                        'title'     => $p,
                        'active'    => ($page == $p),
                        'class'     => str_replace('-xs', '', 'd-none d-'. implode('-block d-', $resolutions) .'-block')
                    );
                }

                if ($page < $nr_of_pages) {
                    $links[] = array(
                        'url'       => Web::url(Web::key(), Web::params(), Web::language(), $page + 1, $_GET),
                        'title'     => $config['icons']['right'],
                        'active'    => false
                    );
                }
                if ($page < ($nr_of_pages - 6)) {
                    $links[] = array(
                        'url'       => Web::url(Web::key(), Web::params(), Web::language(), $nr_of_pages, $_GET),
                        'title'     => $config['icons']['last'],
                        'active'    => false
                    );
                }
            }

            return $links;
        });

        ob_start(); ?>

            <div class="arshmodule-piece-pagination">
                <div class="row align-items-center">
                    <div class="col-sm-4 col-xl-2 text-center text-sm-left margin-1st-1st">
                        <span class="bg-primary text-light p-2 d-inline-block">
                            <span class="badge badge-light"><?= $config['count'] ?></span>
                            <?= $config['text'] ?>
                            <span class="sr-only"><?= $config['count'] ?> <?= $config['text'] ?></span>
                        </span>
                    </div>
                    <div class="col-sm-8 col-xl-9 margin-1st-1st">
                        <ul class="pagination justify-content-center">
                            <?php
                            foreach ($links as $link) { ?>
                                <li class="page-item <?= ($link['active'] ? 'active' : '') ?> <?= ($link['class'] ?? '') ?>">
                                    <?php
                                    if ($link['active']) { ?>
                                        <span class="page-link">
                                            <?= $link['title'] ?>
                                            <span class="sr-only">(current)</span>
                                        </span>
                                    <?php }
                                    else { ?>
                                        <a href="<?= $link['url'] ?>" class="page-link">
                                            <?= $link['title'] ?>
                                        </a>
                                    <?php } ?>
                                </li>
                            <?php } ?>
                        </ul>
                    </div>
                </div>
            </div>

        <?php
        return ob_get_clean();
    }
}
