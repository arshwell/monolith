<?php

namespace ArshWell\Monolith\Module\Request\Backend\Feature;

use ArshWell\Monolith\Table\TableColumn;
use ArshWell\Monolith\Table\TableField;
use ArshWell\Monolith\Table\TableFiles;
use ArshWell\Monolith\File;
use ArshWell\Monolith\Text;
use ArshWell\Monolith\URL;
use ArshWell\Monolith\DB;

final class Update {

    static function GET (array $back, array $query): array {
        $response = array(
            'access' => call_user_func(function ($access) use ($query) {
                return (is_bool($access) ? $access : $access($query['id']));
            }, ($back['features']['update']['response']['access'] ?? !empty($back['features']['update']))),
            'redirect' => call_user_func(function ($url) use ($query) {
                return (is_string($url) ? $url : $url($query['id']));
            }, ($back['features']['update']['response']['redirect'] ?? URL::get(true, false)))
        );

        if ($response['access']) {
            $response['data'] = array();

            if (($back['DB']['table'])::count(($back['DB']['table'])::PRIMARY_KEY .' = '. $query['id'])) {
                foreach ($back['fields'] as $key => $field) {
                    $class  = ($field['DB']['join']['table'] ?? $back['DB']['table']);
                    $column = ($field['DB']['join']['column'] ?? $field['DB']['column'] ?? NULL);

                    if ($column) {
                        if (empty($field['DB']['join']) == false) {
                            if (empty($field['DB']['one2many'])) {
                                $id = ($back['DB']['table'])::field(
                                    $field['DB']['column'] . (defined("{$back['DB']['table']}::TRANSLATED") && in_array($field['DB']['column'], ($back['DB']['table'])::TRANSLATED) ? ':lg' : ''),
                                    ($back['DB']['table'])::PRIMARY_KEY .' = '. $query['id']
                                );

                                if ($id) {
                                    $response['data'][$key] = new TableField($class, $id, ($class)::PRIMARY_KEY);
                                }
                            }
                            else if (empty($field['DB']['table']) == false) {
                                $ids = ($field['DB']['table'])::column(
                                    $field['DB']['column'] . (defined("{$field['DB']['table']}::TRANSLATED") && in_array($field['DB']['column'], ($field['DB']['table'])::TRANSLATED) ? ':lg' : ''),
                                    ($back['DB']['table'])::PRIMARY_KEY .' = '. $query['id']
                                );

                                $response['data'][$key] = new TableColumn($class, $field['DB']['column'] .' IN ('. implode(', ', $ids) .')', $column);
                            }
                        }
                        else {
                            $response['data'][$key] = new TableField($class, $query['id'], $column);
                        }
                    }
                    else if (empty($field['DB'])) {
                        $response['data'][$key] = (new TableFiles($class, $query['id']))->get($key);
                    }
                }

                /**
                 * $response['options'] is used in frontend by <select> elements.
                 *
                 * @param array $response['options'] with values from database.
                 *
                 *    variant 1 | key->value options:
                 *
                 *        $response['options'] = [
                 *            (int) primary key => (string) value,
                 *            (int) primary key => (string) value,
                 *            (int) primary key => (string) value,
                 *            ...
                 *        ]
                 *
                 *    variant 2 | optgroup->values options:
                 *
                 *        $response['options'] = [
                 *            (string) optgroup name => [
                 *                (int) primary key => (string) value,
                 *                (int) primary key => (string) value,
                 *                (int) primary key => (string) value,
                 *                ...
                 *            ]
                 *            (string) optgroup name => [
                 *                (int) primary key => (string) value,
                 *                (int) primary key => (string) value,
                 *                ...
                 *            ]
                 *        ]
                 */
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

                        $sql = \ArshWell\Monolith\SQL::joinsField2joinsQuery(
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
                }
            }
        }

        if (!empty($back['PHP']['validation']['hooks']['get'])) {
            $back['PHP']['validation']['hooks']['get']($query, $response);
        }
        if (!empty($back['actions']['update']['hooks']['get'])) {
            $back['actions']['update']['hooks']['get']($query, $response);
        }

        return array(
            'back'      => $back,
            'query'     => $query,
            'request'   => 'feature/update',
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
            'id' => array(
                'required|int',
                "inDB:{$back['DB']['table']},".($back['DB']['table'])::PRIMARY_KEY
            ),
            'ftr' => array(
                'required|is_string|equal:update'
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
                                            $array[$lg] = ($field['PHP']['rules']['update'] ?? $field['PHP']['rules']);
                                        }

                                        return $array;
                                    }
                                );
                            }
                            else { // is not translated
                                $array[$key] = ($field['PHP']['rules']['update'] ?? $field['PHP']['rules']);
                            }
                        }
                    }

                    return $array;
                }
            ),
            'filetools' => array(
        		"optional|array",
        		"delete" => array(
        			"optional|array",
                    'data' => array(
                        function ($p_key, $value) use ($back) {
                            $array = array(
                                "required|array"
                            );

                            foreach ($back['fields'] as $key => $field) {
                                if (empty($field['DB'])) { // if is file
                                    $array[$key] = array(
                                        "optional|array",
                                        function ($key, $lg, $value) use ($back) {
                                            if (!in_array($lg, (($back['DB']['table'])::TRANSLATOR)::LANGUAGES)) {
                                                return "Inexistent language";
                                            }
                                        },
                                        function ($key, $input) use ($back) {
                                            $array = array();

                                            foreach ((($back['DB']['table'])::TRANSLATOR)::LANGUAGES as $lg) {
                                                if (is_array($input)) {
                                                    $array[$lg] = array(
                                                        array(
                                                            "required|equal:1"
                                                        )
                                                    );
                                                }
                                                else {
                                                    $array[$lg] = array(
                                                        "required|equal:1"
                                                    );
                                                }
                                            }

                                            return $array;
                                        }
                                    );
                                }
                            }

                            return $array;
                        }
                    )
        		),
                "rename" => array(
        			"optional|array",
                    'data' => array(
                        function ($p_key, $value) use ($back) {
                            $array = array(
                                "required|array"
                            );

                            foreach ($back['fields'] as $key => $field) {
                                if (empty($field['DB'])) { // if is file
                                    $array[$key] = array(
                                        "optional|array",
                                        function ($key, $lg, $value) use ($back) {
                                            if (!in_array($lg, (($back['DB']['table'])::TRANSLATOR)::LANGUAGES)) {
                                                return "Inexistent language";
                                            }
                                        },
                                        function ($key, $input) use ($back) {
                                            $array = array();

                                            foreach ((($back['DB']['table'])::TRANSLATOR)::LANGUAGES as $lg) {
                                                if (is_array($input)) {
                                                    $array[$lg] = array(
                                                        array(
                                                            "required",
                                                            function ($filename) {
                                                                return basename(Text::slug($filename));
                                                            }
                                                        )
                                                    );
                                                }
                                                else {
                                                    $array[$lg] = array(
                                                        "required",
                                                        function ($filename) {
                                                            return basename(Text::slug($filename));
                                                        }
                                                    );
                                                }
                                            }

                                            return $array;
                                        }
                                    );
                                }
                            }

                            return $array;
                        }
                    )
        		)
        	)
        );

        if (!empty($files['data'])) {
            $query['data'] = array_merge_recursive($query['data'], File::reformat($files['data'], 2));
        }

        $form = ($back['PHP']['validation']['class'])::run($query, $rules);

        if ($form->valid()) {
            $table = new $back['DB']['table'](array(
                ($back['DB']['table'])::PRIMARY_KEY => $query['id']
            ), true);

            foreach ($form->array('filetools.delete.data') as $key => $input) {
                if ($input) {
                    foreach ($input as $lg => $value) {
                        if ($value) {
                            $file = $table->file($key);

                            switch (get_class($file)) {
                                case 'ArshWell\Monolith\Table\Files\Doc':
                                case 'ArshWell\Monolith\Table\Files\Image': {
                                    $file->delete($lg ?: NULL);
                                    break;
                                }
                                case 'ArshWell\Monolith\Table\Files\ImageGroup': {
                                    $file->delete($value, $lg ?: NULL);
                                    break;
                                }
                            }
                        }
                    }
                }
        	}

            foreach ($form->array('filetools.rename.data') as $key => $input) {
                if ($input) {
                    foreach ($input as $lg => $value) {
                        $table->file($key)->rename($value, $lg);
                    }
                }
        	}

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
                        if (!isset($back['fields'][$key]['DB']['table'])) {
                            $table->{$column} = $value;
                        }
                        else {
                            if (is_array($value)) {
                                ($back['fields'][$key]['DB']['table'])::delete(
                                    ($back['DB']['table'])::PRIMARY_KEY .' = ? AND '. $column .' NOT IN ('.implode(', ', $value).')',
                                    array($query['id'])
                                );
                                foreach ($value as $v) {
                                    if (!($back['fields'][$key]['DB']['table'])::count(
                                        ($back['DB']['table'])::PRIMARY_KEY .' = ? AND '. $column .' = ?',
                                        array($query['id'], $v)
                                    )) {
                                        ($back['fields'][$key]['DB']['table'])::insert(
                                            ($back['DB']['table'])::PRIMARY_KEY .', '. $column,
                                            "?, ?",
                                            array($query['id'], $v)
                                        );
                                    }
                                }
                            }
                            else {
                                if (!($back['fields'][$key]['DB']['table'])::count(
                                    ($back['DB']['table'])::PRIMARY_KEY .' = ?',
                                    array($query['id'])
                                )) {
                                    ($back['fields'][$key]['DB']['table'])::insert(
                                        ($back['DB']['table'])::PRIMARY_KEY .', '. $column,
                                        "?, ?",
                                        array($query['id'], $value)
                                    );
                                }
                                else {
                                    ($back['fields'][$key]['DB']['table'])::update(
                                        array(
                                            'set'   => $column .' = ?',
                                            'where' => ($back['DB']['table'])::PRIMARY_KEY .' = ?'
                                        ),
                                        array($value, $query['id'])
                                    );
                                }
                            }
                        }
                    }
                    else if ($value) {
                        $file = $table->file($key);

                        switch (get_class($file)) {
                            case 'ArshWell\Monolith\Table\Files\Doc':
                            case 'ArshWell\Monolith\Table\Files\Image': {
                                $file->update($value, $column ?: NULL); // $column is lg
                                break;
                            }
                            case 'ArshWell\Monolith\Table\Files\ImageGroup': {
                                $file->insert($value, $column ?: NULL); // $column is lg
                                break;
                            }
                        }
                    }
                }
            }

            $table->edit();

            if (!empty($back['PHP']['validation']['valid'])) {
                $back['PHP']['validation']['valid']();
            }

            $form->message = array(
                'type' => 'success',
                'text' => "Editat cu succes"
            );

            switch ($form->value('after')) {
                case 'select': {
                    $form->redirect = URL::get(true, false);
                    break;
                }
                case 'insert': {
                    $form->redirect = URL::get(true, false) . '?ctn=insert';
                    break;
                }
            }
        }
        else {
            $form->message = array(
                'type' => 'danger',
                'text' => "Fields filled in incorrectly"
            );
        }

        if (!empty($back['PHP']['validation']['hooks']['ajax'])) {
            $back['PHP']['validation']['hooks']['ajax']($query, $form);
        }
        if (!empty($back['actions']['update']['hooks']['ajax'])) {
            $back['features']['update']['hooks']['ajax']($query, $form);
        }

        return $form->json();
    }
}
