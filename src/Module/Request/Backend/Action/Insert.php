<?php

namespace Arshwell\Monolith\Module\Request\Backend\Action;

use Arshwell\Monolith\Table\TableFiles;
use Arshwell\Monolith\File;
use Arshwell\Monolith\URL;
use Arshwell\Monolith\DB;

final class Insert {

    static function GET (array $back, array $query): array {
        $response = array(
            'access' => call_user_func(function ($access) {
                return (is_bool($access) ? $access : $access());
            }, ($back['actions']['insert']['response']['access'] ?? !empty($back['actions']['insert']))),
            'redirect' => call_user_func(function ($url) {
                return (is_string($url) ? $url : $url());
            }, ($back['actions']['insert']['response']['redirect'] ?? URL::get(true, false)))
        );

        if ($response['access']) {
            $files = array();
            $response['data'] = array();

            foreach ($back['fields'] as $key => $field) {
                // one single join
                if (isset($field['DB']['join'])) {
                    $suffix = (($field['DB']['join']['table'])::translationTimes($field['DB']['join']['column']) ? ':lg' : '');

                    $response['options'][$key] = array_column(
                        DB::select(
                            array(
                                'class'     => $field['DB']['join']['table'],
                                'columns'   => ($field['DB']['join']['table'])::PRIMARY_KEY .', '. $field['DB']['join']['column'].$suffix .' AS '. $field['DB']['join']['column']
                            ),
                            (($back['DB']['table'])::translationTimes() > 1 ? array(':lg' => (($back['DB']['table'])::TRANSLATOR)::default()) : array())
                        ),
                        $field['DB']['join']['column'], ($field['DB']['join']['table'])::PRIMARY_KEY
                    );
                }

                // multiple joins
                else if (isset($field['DB']['joins'])) {
                    $optgroup_columns = array_map(function ($value) {
                        return ($value['table'])::TABLE .'_'. $value['column'];
                    }, $field['DB']['joins']);

                    $option_column = array_shift($optgroup_columns);

                    $optgroup_columns = array_reverse(array_flip($optgroup_columns));

                    $join = array_shift($field['DB']['joins']);

                    $sql = \Arshwell\Monolith\SQL::joinsField2joinsQuery(
                        $join['table'], $join['column'], $field['DB']['joins'], $lgs
                    );

                    $rows = DB::select(
                        $sql, array(':lg' => $lgs)
                    );

                    $options = [];

                    foreach ($rows as $row) {
                        $optgroup_name = implode(' > ', array_replace($optgroup_columns, array_intersect_key($row, $optgroup_columns)));

                        $options[$optgroup_name][$row[$field['DB']['column']]] = $row[$option_column];
                    }

                    $response['options'][$key] = $options;
                }

                // is file
                else if (empty($field['DB'])) {
                    if (!isset($files[$key])) {
                        $files[$key] = new TableFiles($back['DB']['table'], NULL);
                    }
                    $response['data'][$key] = $files[$key]->get($key);
                }
            }
        }

        if (!empty($back['PHP']['validation']['hooks']['get'])) {
            $back['PHP']['validation']['hooks']['get']($query, $response);
        }
        if (!empty($back['actions']['insert']['hooks']['get'])) {
            $back['actions']['insert']['hooks']['get']($query, $response);
        }

        return array(
            'back'      => $back,
            'query'     => $query,
            'request'   => 'action/insert',
            'response'  => $response
        );
    }

    static function AJAX (array $back, array $query, array $files): string {
        if (empty($query['data']) || !is_array($query['data'])) {
            http_response_code(503);
            exit;
        }

        $back['is_translated'] = defined("{$back['DB']['table']}::TRANSLATED");

        $rules = array(
            'ctn' => array(
                'required|is_string|equal:insert'
            ),
            'after' => array(
                'optional|is_string|inArray:select,insert,update,duplicate'
            ),
            'data' => array(
                function ($p_key, $value) use ($back) {
                    $array = array(
                        "required|array"
                    );

                    foreach ($back['fields'] as $key => $field) {
                        if (!empty($field['PHP']['rules'])) {
                            // if field is translated
                            if (empty($field['DB'])
                            || ($back['is_translated'] && in_array($field['DB']['column'], ($back['DB']['table'])::TRANSLATED))) {
                                $array[$key] = array(
                                    function ($value) {
                                        if ($value == NULL) {
                                            $value = array();
                                        }
                                        return $value;
                                    },
                                    "array",
                                    function ($key, $lg, $value) use ($back) {
                                        if (!in_array($lg, (($back['DB']['table'])::TRANSLATOR)::LANGUAGES)) {
                                            return "Inexistent language";
                                        }
                                    },
                                    function ($key, $input) use ($back, $field) {
                                        $array = array();

                                        foreach ((($back['DB']['table'])::TRANSLATOR)::LANGUAGES as $lg) {
                                            $array[$lg] = ($field['PHP']['rules']['insert'] ?? $field['PHP']['rules']);
                                        }

                                        return $array;
                                    }
                                );
                            }
                            else { // is not translated
                                $array[$key] = ($field['PHP']['rules']['insert'] ?? $field['PHP']['rules']);
                            }
                        }
                    }

                    return $array;
                }
            )
        );

        if (!empty($files['data'])) {
            $query['data'] = array_merge_recursive($query['data'], File::reformat($files['data'], 2));
        }

        $form = ($back['PHP']['validation']['class'])::run($query, $rules);

        if ($form->valid()) {
            $table = new $back['DB']['table'](NULL, true);

            foreach ($form->value('data') as $key => $input) {
                if ($back['fields'][$key]['DB']) {
                    $columns = array();

                    if ($back['is_translated'] && in_array($key, ($back['DB']['table'])::TRANSLATED)) {
                        foreach ($input as $lg => $v) {
                            $columns[$back['fields'][$key]['DB']['column'].'_'.$lg] = $v;
                        }
                    }
                    else {
                        $columns[$back['fields'][$key]['DB']['column']] = $input;
                    }

                    foreach ($columns as $column => $value) {
                        if (!isset($back['fields'][$key]['DB']['table'])) {
                            $table->{$column} = $value;
                        }
                    }
                }
            }

            $table->add();

            foreach ($form->value('data') as $key => $input) {
                $columns = array();

                if (empty($back['fields'][$key]['DB'])) {
                    $columns = (array)$input;
                }
                else if ($back['is_translated'] && in_array($key, ($back['DB']['table'])::TRANSLATED)) {
                    foreach ($input as $lg => $v) {
                        $columns[$back['fields'][$key]['DB']['column'].'_'.$lg] = $v;
                    }
                }
                else {
                    $columns[$back['fields'][$key]['DB']['column']] = $input;
                }

                foreach ($columns as $column => $value) {
                    if ($back['fields'][$key]['DB']) {
                        // we do it here because we need an id
                        if (isset($back['fields'][$key]['DB']['table'])) {
                            foreach ((array)$value as $v) {
                                ($back['fields'][$key]['DB']['table'])::insert(
                                    ($back['DB']['table'])::PRIMARY_KEY .', '. $column,
                                    "?, ?",
                                    array($table->id(), $v)
                                );
                            }
                        }
                    }
                    else if ($value) {
                        $file = $table->file($key);

                        switch (get_class($file)) {
                            case 'Arshwell\Monolith\Table\Files\Doc':
                            case 'Arshwell\Monolith\Table\Files\Image': {
                                $file->update($value, $column ?: NULL); // $column is lg
                                break;
                            }
                            case 'Arshwell\Monolith\Table\Files\ImageGroup': {
                                $file->insert($value, $column ?: NULL); // $column is lg
                                break;
                            }
                        }
                    }
                }
            }

            $form->message = array(
                'type' => "success",
                'text' => "Added successfully"
            );

            switch ($form->value('after')) {
                case 'select': {
                    $form->redirect = URL::get(true, false);
                    break;
                }
                case 'update': {
                    $form->redirect = URL::get(true, false) . '?ftr=update&id='.$table->id();
                    break;
                }
            }

            $form->id = $table->id(); // NOTE: for sending to hook
        }
        else {
            $form->message = array(
                'type' => "danger",
                'text' => "Fields filled in incorrectly"
            );
        }

        if (!empty($back['PHP']['validation']['hooks']['ajax'])) {
            $back['PHP']['validation']['hooks']['ajax']($query, $form);
        }
        if (!empty($back['actions']['insert']['hooks']['ajax'])) {
            $back['actions']['insert']['hooks']['ajax']($query, $form);
        }

        return $form->json();
    }
}
