<?php

namespace ArshWell\Monolith\Module\Request\Backend\Action;

use ArshWell\Monolith\Table\TableColumn;
use ArshWell\Monolith\Table\TableField;
use ArshWell\Monolith\Table\TableFiles;
use ArshWell\Monolith\Web;
use ArshWell\Monolith\URL;
use ArshWell\Monolith\DB;

class Select {

    static function GET (array $back, array $query): array {
        $response = array(
            'access' => call_user_func(function ($access) use ($query) {
                return (is_bool($access) ? $access : $access());
            }, ($back['actions']['select']['response']['access'] ?? !empty($back['actions']['select']))),
            'redirect' => call_user_func(function ($url) use ($query) {
                return (is_string($url) ? $url : $url());
            }, ($back['actions']['select']['response']['redirect'] ?? URL::get(true, false)))
        );

        if ($response['access']) {
            $limit = ($query['limit'] ?? $back['actions']['select']['limit'] ?? 20);

            if (empty($query['search']) || !is_array($query['search'])) {
                unset($query['search']);
            }
            if (empty($query['filter']) || !is_array($query['filter'])) {
                unset($query['filter']);
            }

            $query['columns'] = array_diff(
                $query['columns'] ?? $back['actions']['select']['columns']['public'] ?? array_keys($back['fields']),
                $back['actions']['select']['columns']['private'] ?? array()
            );

            if (empty($query['sort']) || !is_array($query['sort'])) {
                unset($query['sort']);
            }
            if (($back['DB']['table'])::translationTimes() > 0 && empty($query['lg'])) {
                $query['lg'] = (($back['DB']['table'])::TRANSLATOR)::default();
            }

            $where = array();
            $order = NULL;

            if (isset($query['search'])) {
                $where[] = ('(' . implode(' OR ', call_user_func(function ($search) use ($back) {
                    foreach ($search as $key => $values) {
                        $search[$key] = ('(' . implode(' OR ', array_map(function ($value) use ($back, $key) {
                            $column = $back['fields'][$key]['DB']['column'];
                            $suffix = (($back['DB']['table'])::translationTimes($column) > 0 ? ':lg' : '');

                            return ($back['DB']['table'])::TABLE .'.'. ($value ? ($column.$suffix .' LIKE "%'. $value .'%"') : ($column.$suffix .' = ""'));
                        }, $values)) . ')');
                    }

                    return $search;
                }, $query['search'])) . ')');
            }
            if (isset($query['filter'])) {
                $where[] = ('(' . implode(' OR ', call_user_func(function ($filter) use ($back) {
                    foreach ($filter as $key => $values) {
                        $filter[$key] = ('(' . implode(' OR ', array_map(function ($value) use ($back, $key) {
                            $column = $back['fields'][$key]['DB']['column'];
                            $suffix = (($back['DB']['table'])::translationTimes($column) > 0 ? ':lg' : '');

                            return ($back['DB']['table'])::TABLE .'.'. ($column.$suffix . (is_numeric($value) ? (' = '. $value) : (' = "'. $value .'"')));
                        }, $values)) . ')');
                    }

                    return $filter;
                }, $query['filter'])) . ')');
            }

            $where = (implode(' OR ', $where) ?: NULL);

            $userHasAccessToFeature = call_user_func(function ($access) use ($query) {
                return (is_bool($access) ? $access : $access());
            }, ($back['features']['order']['response']['access'] ?? !empty($back['features']['order'])));

            if ($userHasAccessToFeature) {
                $order = (
                    ($back['DB']['table'])::TABLE .'.'. ("`". ($back['features']['order']['column'] ?? 'order') ."`") ." ASC"
                    .', '.
                    (($back['DB']['table'])::PRIMARY_KEY . ' DESC')
                );
            }
            if (isset($query['sort'])) {
                $order = implode(', ', call_user_func(function ($sort) use ($back) {
                    foreach ($sort as $key => $value) {
                        $column = $back['fields'][$key]['DB']['column'];
                        $suffix = (($back['DB']['table'])::translationTimes($column) ? ':lg' : '');

                        $sort[$key] = ($back['DB']['table'])::TABLE .'.'. ($column.$suffix . ($value == 'd' ? ' DESC' : ' ASC'));
                    }

                    return $sort;
                }, $query['sort']));
            }

            $response['count'] = ($back['DB']['table'])::count($where);
            $response['limit'] = $limit;
            $response['data'] = array_column(DB::select(array(
                'class'     => $back['DB']['table'],
                'columns'   => ($back['DB']['table'])::PRIMARY_KEY,
                'where'     => $where,
                'order'     => ($order ?? (($back['DB']['table'])::PRIMARY_KEY . ' DESC')),
                'limit'     => $limit,
                'offset'    => (Web::page() > 1 ? $limit * (Web::page() - 1) : 0),
                'files'     => false
            )), NULL, ($back['DB']['table'])::PRIMARY_KEY);

            foreach ($response['data'] as $id_table => &$row) {
                foreach ($back['fields'] as $key => $field) {
                    $class  = ($field['DB']['join']['table'] ?? $back['DB']['table']);
                    $column = ($field['DB']['join']['column'] ?? $field['DB']['column'] ?? NULL);

                    if (in_array($key, $query['columns'])) {
                        if ($column) {
                            if (empty($field['DB']['one2many'])) {
                                // single join
                                if (!empty($field['DB']['join']['table'])) {
                                    $row[$key] = new TableField(
                                        $class,
                                        ($back['DB']['table'])::field($field['DB']['column'], ($back['DB']['table'])::PRIMARY_KEY .' = '. $id_table),
                                        $column
                                    );
                                }

                                // multiple joins
                                else if (!empty($field['DB']['joins'])) {
                                    $class  = ($field['DB']['joins'][0]['table']);
                                    $column = ($field['DB']['joins'][0]['column']);

                                    $row[$key] = new TableField(
                                        $class,
                                        ($back['DB']['table'])::field($field['DB']['column'], ($back['DB']['table'])::PRIMARY_KEY .' = '. $id_table),
                                        $column
                                    );

                                    $sql = \Arsavinel\Arshwell\SQL::joinsField2joinsQuery(
                                        $back['DB']['table'], $field['DB']['column'], $field['DB']['joins'], $lgs
                                    );

                                    $sql['where'] = (($back['DB']['table'])::TABLE.'.'.($back['DB']['table'])::PRIMARY_KEY .' = '. $id_table);

                                    $record = DB::first(
                                        $sql, array(':lg' => $lgs)
                                    );

                                    if ($record) {
                                        $optgroup_columns = array_map(function ($value) {
                                            return ($value['table'])::TABLE .'_'. $value['column'];
                                        }, $field['DB']['joins']);

                                        $option_column = array_shift($optgroup_columns);

                                        $optgroup_columns = array_reverse(array_flip($optgroup_columns));

                                        $optgroup_name = implode(' > ', array_replace($optgroup_columns, array_intersect_key($record, $optgroup_columns)));

                                        // this property is used by Piece::body()
                                        $row[$key]->suptitle = implode(' > ', array_replace($optgroup_columns, array_intersect_key($record, $optgroup_columns)));
                                    }
                                }

                                // column of this table class
                                else {
                                    $row[$key] = new TableField($class, $id_table, $column);
                                }
                            }
                            else {
                                $ids = ($field['DB']['table'])::column(
                                    $field['DB']['column'] . ($field['DB']['table']::translationTimes($field['DB']['column']) > 0 ? ':lg' : ''),
                                    ($back['DB']['table'])::PRIMARY_KEY .' = '. $id_table
                                );

                                $row[$key] = new TableColumn($class, $field['DB']['column'] .' IN ('. implode(', ', $ids) .')', $column);
                            }
                        }

                        // is file
                        else if (empty($field['DB'])) {
                            $row[$key] = (new TableFiles($class, $id_table))->get($key);
                        }
                    }
                }

                unset($row);
            }

            foreach ($back['fields'] as $key => $field) {
                // one single join
                if (isset($field['DB']['join'])) {
                    $suffix = (($field['DB']['join']['table'])::translationTimes($field['DB']['join']['column']) > 0 ? ':lg' : '');

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

                    $sql = \Arsavinel\Arshwell\SQL::joinsField2joinsQuery(
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

        if (!empty($back['PHP']['validation']['hooks']['get'])) {
            $back['PHP']['validation']['hooks']['get']($query, $response);
        }
        if (!empty($back['actions']['select']['hooks']['get'])) {
            $back['actions']['select']['hooks']['get']($query, $response);
        }

        return array(
            'back'      => $back,
            'query'     => $query,
            'request'   => 'action/select',
            'response'  => $response
        );
    }
}
