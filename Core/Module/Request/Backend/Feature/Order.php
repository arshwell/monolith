<?php

namespace Arsavinel\Arshwell\Module\Request\Backend\Feature;

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
            $start = ($back['DB']['table'])::count(
                ($back['DB']['table'])::PRIMARY_KEY." <= ?",
                array(min($form->value('ids')))
            );
            foreach ($form->value('ids') as $index => $id) {
                ($back['DB']['table'])::update(
                    array(
                        'set'   => "`".$back['features']['order']['column']."` = ?",
                        'where' => ($back['DB']['table'])::PRIMARY_KEY . " = ?"
                    ),
                    array($start + $index, $id)
                );
            }

            if (!empty($back['PHP']['validation']['valid'])) {
                $back['PHP']['validation']['valid']();
            }
        }
        else {
            $form->message = array(
                'type' => 'danger',
                'text' => "Câmpuri completate greșit"
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
