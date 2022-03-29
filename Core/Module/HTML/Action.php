<?php

namespace Arsh\Core\Module\HTML;

final class Action {

    static function link (string $key, array $action): string {
        ob_start();

            if (empty($action['HTML']['hidden'])) { ?>
                <a
                data-key="<?= $key ?>"
                class="<?= $action['HTML']['class'] ?> <?= (($action['HTML']['disabled'] ?? false) ? 'disabled' : '') ?> arshmodule-html arshmodule-html-action arshmodule-html-action-link"
                <?= (empty($action['HTML']['disabled']) ? 'href="'. $action['HTML']['href'] .'"' : '') ?>
                <?= (($action['HTML']['disabled'] ?? false) ? 'role="link" aria-disabled="true"' : '') ?>
                <?= (!empty($action['HTML']['title']) ? 'title="'.$action['HTML']['title'].'"' : '') ?>
                target="<?= $action['HTML']['target'] ?? '_self' ?>">
                    <?php
                    if ($action['HTML']['icon']) { ?>
                        <i class="fa fa-fw fa-<?= $action['HTML']['icon'] ?>"></i>
                    <?php } ?>
                    <?= $action['HTML']['text'] ?? '' ?>
                </a>
            <?php }

        return ob_get_clean();
    }

    static function button (string $key, array $action): string {
        ob_start();

            if (empty($action['HTML']['hidden'])) { ?>
                <button
                type="button"
                data-key="<?= $key ?>"
                <?= (($action['HTML']['disabled'] ?? false) ? 'role="link" aria-disabled="true"' : '') ?>
                class="<?= $action['HTML']['class'] ?> <?= (($action['HTML']['disabled'] ?? false) ? 'disabled' : '') ?> arshmodule-html arshmodule-html-action arshmodule-html-action-button">
                    <?php
                    if ($action['HTML']['icon']) { ?>
                        <i class="fa fa-fw fa-<?= $action['HTML']['icon'] ?>"></i>
                    <?php } ?>
                    <?= $action['HTML']['text'] ?>
                </button>
            <?php }

        return ob_get_clean();
    }
}
