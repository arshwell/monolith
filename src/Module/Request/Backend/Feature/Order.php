<?php

namespace Arshwell\Monolith\Module\Request\Backend\Feature;

use Arshwell\Monolith\DB;

final class Order {

    static function AJAX (array $back, array $query): string {
        $form = ($back['PHP']['validation']['class'])::run($query, array(
            'ftr' => array(
                'required|is_string|equal:order'
            ),
            'ids' => array(
                "required|array",
                array(
                    "int",
                    "inDB:{$back['DB']['table']},".($back['DB']['table'])::PRIMARY_KEY
                )
            )
        ));

        if ($form->valid()) {
            $orderings = ($back['DB']['table'])::column(
                "`order`",
                ($back['DB']['table'])::PRIMARY_KEY." IN (". implode(',', $form->value('ids')) .")"
            );

            $orderings_with_nr_zero = array_filter($orderings, function ($o) {return $o == 0;});

            /**
             * Min order from which we start the reordering.
             *
             * If some rows have 0 order, we want also them to get a real order.
             * So we start ordering from their needed order value.
             */
            $order_start = max(1, min($orderings) - count($orderings_with_nr_zero));

            $ids = $form->value('ids');

            /**
             * If we'll give order values to zero ordering values,
             * we need to reorder all the following rows.
             *
             * Because we'll use some ordering values already used by the following rows.
             */
            if ($orderings_with_nr_zero) {
                $ids_of_following_rows = DB::select(
                    array(
                        'class'     => $back['DB']['table'],
                        'columns'   => ($back['DB']['table'])::PRIMARY_KEY,
                        'where'     => "`{$back['features']['order']['column']}` > ?",
                        'order'     => "`{$back['features']['order']['column']}` ASC, ".($back['DB']['table'])::PRIMARY_KEY." DESC",
                    ),
                    array(max($orderings))
                );

                $ids = array_merge($ids, array_column($ids_of_following_rows, ($back['DB']['table'])::PRIMARY_KEY));
            }

            /**
             * We don't reorder the entire table.
             * But we only reorder, with given ids, their existing order values.
             */
            foreach ($ids as $index => $id) {
                ($back['DB']['table'])::update(
                    array(
                        'set'   => "`{$back['features']['order']['column']}` = ?",
                        'where' => ($back['DB']['table'])::PRIMARY_KEY . " = ?"
                    ),
                    array($order_start + $index, $id)
                );
            }

            if (!empty($back['PHP']['validation']['valid'])) {
                $back['PHP']['validation']['valid']();
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
        if (!empty($back['actions']['order']['hooks']['ajax'])) {
            $back['features']['order']['hooks']['ajax']($query, $form);
        }

        return $form->json();
    }
}
