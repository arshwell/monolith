<?php

namespace Arsavinel\Arshwell\Module\Request\Backend\Action;

use Arsavinel\Arshwell\Table\TableColumn;
use Arsavinel\Arshwell\Table\TableField;
use Arsavinel\Arshwell\Table\TableFiles;
use Arsavinel\Arshwell\Web;
use Arsavinel\Arshwell\URL;
use Arsavinel\Arshwell\DB;

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
            $back['is_translated'] = defined("{$back['DB']['table']}::TRANSLATED");
            $limit = ($back['actions']['select']['limit'] ?? 20);

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
            if ($back['is_translated'] && empty($query['lg'])) {
                $query['lg'] = (($back['DB']['table'])::TRANSLATOR)::default();
            }

            $where = array();
            $order = NULL;
            $params = array();

            if (isset($query['search'])) {
                $where[] = ('(' . implode(' OR ', call_user_func(function ($search) use ($back) {
                    foreach ($search as $key => $values) {
                        $search[$key] = ('(' . implode(' OR ', array_map(function ($value) use ($back, $key) {
                            $column = $back['fields'][$key]['DB']['column'];
                            $suffix = ($back['is_translated'] && in_array($column, ($back['DB']['table'])::TRANSLATED) ? ':lg' : '');

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
                            $suffix = ($back['is_translated'] && in_array($column, ($back['DB']['table'])::TRANSLATED) ? ':lg' : '');

                            return ($back['DB']['table'])::TABLE .'.'. ($column.$suffix . (is_numeric($value) ? (' = '. $value) : (' = "'. $value .'"')));
                        }, $values)) . ')');
                    }

                    return $filter;
                }, $query['filter'])) . ')');
            }

            $where = (implode(' OR ', $where) ?: NULL);

            if (call_user_func(function ($access) use ($query) {
                return (is_bool($access) ? $access : $access());
            }, ($back['features']['order']['response']['access'] ?? !empty($back['features']['order'])))) {
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
                        $suffix = ($back['is_translated'] && in_array($column, ($back['DB']['table'])::TRANSLATED) ? ':lg' : '');

                        $sort[$key] = ($back['DB']['table'])::TABLE .'.'. ($column.$suffix . ($value == 'd' ? ' DESC' : ' ASC'));
                    }

                    return $sort;
                }, $query['sort']));
            }

            $response['count'] = ($back['DB']['table'])::count($where);
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
                    $class  = ($field['DB']['from']['table'] ?? $back['DB']['table']);
                    $column = ($field['DB']['from']['column'] ?? $field['DB']['column'] ?? NULL);

                    if (in_array($key, $query['columns'])) {
                        if ($column) {
                            if (empty($field['DB']['one2many'])) {
                                if (empty($field['DB']['from']['table'])) {
                                    $row[$key] = new TableField($class, $id_table, $column);
                                }
                                else {
                                    $row[$key] = new TableField(
                                        $class,
                                        ($back['DB']['table'])::field($field['DB']['column'], ($back['DB']['table'])::PRIMARY_KEY .' = '. $id_table),
                                        $column
                                    );
                                }
                            }
                            else {
                                $ids = ($field['DB']['table'])::column(
                                    $field['DB']['column'] . (defined("{$field['DB']['table']}::TRANSLATED") && in_array($field['DB']['column'], ($field['DB']['table'])::TRANSLATED) ? ':lg' : ''),
                                    ($back['DB']['table'])::PRIMARY_KEY .' = '. $id_table
                                );

                                $row[$key] = new TableColumn($class, $field['DB']['column'] .' IN ('. implode(', ', $ids) .')', $column);
                            }
                        }
                        else if (empty($field['DB'])) {
                            $row[$key] = (new TableFiles($class, $id_table))->get($key);
                        }
                    }
                }

                unset($row);
            }

            foreach ($back['fields'] as $key => $field) {
                if (isset($field['DB']['from'])) {
                    $column = $field['DB']['from']['column'];
                    $suffix = (defined("{$field['DB']['from']['table']}::TRANSLATED") && in_array($column, ($field['DB']['from']['table'])::TRANSLATED) ? ':lg' : '');

                    $response['options'][$key] = array_column(
                        DB::select(
                            array(
                                'class'     => $field['DB']['from']['table'],
                                'columns'   => ($field['DB']['from']['table'])::PRIMARY_KEY .', '. $column.$suffix .' AS '. $column
                            ),
                            (defined("{$field['DB']['from']['table']}::TRANSLATED") ? array(':lg' => (($field['DB']['from']['table'])::TRANSLATOR)::default()) : array())
                        ),
                        $column, ($field['DB']['from']['table'])::PRIMARY_KEY
                    );
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
