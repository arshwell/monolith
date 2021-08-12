<?php

namespace Arsh\Core\Module\Request\Backend\Feature;

final class Delete {

    static function AJAX (array $back, array $query): string {
        $form = ($back['PHP']['validation']['class'])::run($query, array(
            'id' => array(
                "required|int",
                "inDB:{$back['DB']['table']},".($back['DB']['table'])::PRIMARY_KEY
            ),
            'ftr' => array(
                'required|is_string|equal:delete'
            )
        ));

        if ($form->valid()) {
            $table = new $back['DB']['table'](array(
                ($back['DB']['table'])::PRIMARY_KEY => $query['id']
            ), true);

            if ($table->remove() && $table->files()) {
                foreach ($table->files()->toArray() as $file) {
                    $file->delete();
                }
            }

            if (!empty($back['PHP']['validation']['valid'])) {
                $back['PHP']['validation']['valid']();
            }

            $form->remove = 'tr';
            $form->message = array(
                'type' => 'success',
                'text' => "Șters cu succes"
            );
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
        if (!empty($back['actions']['delete']['hooks']['ajax'])) {
            $back['features']['delete']['hooks']['ajax']($query, $form);
        }

        return $form->json();
    }
}
