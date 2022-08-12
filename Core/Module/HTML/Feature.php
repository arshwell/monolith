<?php

namespace Arsavinel\Arshwell\Module\HTML;

use Arsavinel\Arshwell\URL;

final class Feature {

    static function link (string $key, array $feature): string {
        ob_start();

            if (empty($feature['HTML']['hidden'])) { ?>
                <a class="<?= $feature['HTML']['class'] ?> arshmodule-html arshmodule-html-feature arshmodule-html-feature-link"
                <?php
                if (!empty($feature['JS']['tooltip'])) {
                    echo 'data-tooltip="true" ';
                    echo implode(' ', array_map(function ($key, $value) {
                        return ("data-".$key.'="'.$value.'"');
                    }, array_keys($feature['JS']['tooltip']), $feature['JS']['tooltip']));
                } ?>
                <?php
                if (!empty($feature['JS']['confirmation'])) {
                    echo 'data-confirmation="true" ';
                    echo implode(' ', array_map(function ($key, $value) {
                        return ("data-".$key.'="'.$value.'"');
                    }, array_keys($feature['JS']['confirmation']), $feature['JS']['confirmation']));
                } ?>
                <?= (!empty($feature['HTML']['title']) ? 'title="'.$feature['HTML']['title'].'"' : '') ?>
                href="<?= $feature['HTML']['href'] ?>"
                target="<?= $feature['HTML']['target'] ?? '_self' ?>">
                    <?php
                    if ($feature['HTML']['icon']) {
                        switch ($feature['HTML']['icon']['style'] ?? NULL) {
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
                        <i class="<?= $fa_class ?> fa-fw fa-<?= $feature['HTML']['icon']['name'] ?? $feature['HTML']['icon'] ?>" data-toggle="fa-<?= $feature['HTML']['icon']['name'] ?? $feature['HTML']['icon'] ?> fa-spinner"></i>
                    <?php } ?>
                    <?= $feature['HTML']['text'] ?>
                </a>
            <?php }

        return ob_get_clean();
    }

    static function button (string $key, array $feature, int $id_table = 0): string {
        ob_start();

            if (empty($feature['HTML']['hidden'])) { ?>
                <button type="button"
                data-key="<?= $key ?>" data-id-table="<?= $id_table ?>"
                <?php
                if (!empty($feature['JS']['tooltip'])) {
                    echo 'data-tooltip="true"';
                    echo implode(' ', array_map(function ($key, $value) {
                        return ("data-".$key.'="'.$value.'"');
                    }, array_keys($feature['JS']['tooltip']), $feature['JS']['tooltip']));
                }
                if (!empty($feature['JS']['confirmation'])) {
                    echo 'data-confirmation="true"';
                    echo implode(' ', array_map(function ($key, $value) {
                        return ("data-".$key.'="'.$value.'"');
                    }, array_keys($feature['JS']['confirmation']), $feature['JS']['confirmation']));
                }
                if (!empty($feature['JS']['clipboard'])) {
                    echo implode(' ', array_map(function ($key, $value) {
                        return ("data-clipboard-".$key.'="'.$value.'"');
                    }, array_keys($feature['JS']['clipboard']), $feature['JS']['clipboard']));
                } ?>
                <?= (!empty($feature['HTML']['title']) ? 'title="'.$feature['HTML']['title'].'"' : '') ?>
                class="<?= $feature['HTML']['class'] ?> arshmodule-html arshmodule-html-feature arshmodule-html-feature-button">
                    <?php
                    if ($feature['HTML']['icon']) {
                        switch ($feature['HTML']['icon']['style'] ?? NULL) {
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
                        <i class="<?= $fa_class ?> fa-fw fa-<?= $feature['HTML']['icon']['name'] ?? $feature['HTML']['icon'] ?>"></i>
                    <?php } ?>
                    <?= $feature['HTML']['text'] ?>
                </button>
            <?php }

        return ob_get_clean();
    }

    static function submit (string $key, array $feature, int $id_table = 0): string {
        ob_start();

            if (empty($feature['HTML']['hidden'])) { ?>
                <form method="<?= ($feature['JS']['ajax']['type'] ?? 'POST') ?>"
                <?= (!empty($feature['JS']['ajax']['url']) ? 'action="'.$feature['JS']['ajax']['url'].'"' : '') ?>
                class="arshmodule-html arshmodule-html-feature arshmodule-html-feature-submit">
                    <input type="hidden" name="ftr" value="<?= $key ?>" />
                    <input type="hidden" name="id" value="<?= $id_table ?>" />
                    <button type="submit"
                    <?php
                    if (!empty($feature['JS']['tooltip'])) {
                        echo 'data-tooltip="true" ';
                        echo implode(' ', array_map(function ($key, $value) {
                            return ("data-".$key.'="'.$value.'"');
                        }, array_keys($feature['JS']['tooltip']), $feature['JS']['tooltip']));
                    }
                    if (!empty($feature['JS']['confirmation'])) {
                        echo 'data-confirmation="true" ';
                        echo implode(' ', array_map(function ($key, $value) {
                            return ("data-".$key.'="'.$value.'"');
                        }, array_keys($feature['JS']['confirmation']), $feature['JS']['confirmation']));
                    }
                    if (!empty($feature['JS']['clipboard'])) {
                        echo implode(' ', array_map(function ($key, $value) {
                            return ("data-clipboard-".$key.'="'.$value.'"');
                        }, array_keys($feature['JS']['clipboard']), $feature['JS']['clipboard']));
                    } ?>
                    <?= (!empty($feature['HTML']['title']) ? 'title="'.$feature['HTML']['title'].'"' : '') ?>
                    class="<?= $feature['HTML']['class'] ?>">
                        <?php
                        if ($feature['HTML']['icon']) {
                            switch ($feature['HTML']['icon']['style'] ?? NULL) {
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
                            <i class="<?= $fa_class ?> fa-fw fa-<?= $feature['HTML']['icon']['name'] ?? $feature['HTML']['icon'] ?>" data-toggle="<?= $fa_class ?> fa-<?= $feature['HTML']['icon']['name'] ?? $feature['HTML']['icon'] ?> fas fa-spinner"></i>
                        <?php } ?>
                        <?= $feature['HTML']['text'] ?>
                    </button>
                </form>
            <?php }

        return ob_get_clean();
    }

    static function popup (string $key, array $feature, int $id_table = 0): string {
        ob_start();

            if (empty($feature['HTML']['hidden'])) { ?>
                <div class="d-inline-block arshmodule-html arshmodule-html-feature arshmodule-html-feature-popup">
                    <div class="modal fade" data-keyboard="false" tabindex="-1">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content border-0 rounded-0">
                                <iframe class="border w-100" name="module.ftr.popup.<?= $key ?>.<?= $id_table ?>"></iframe>
                            </div>
                        </div>
                    </div>

                    <form target="module.ftr.popup.<?= $key ?>.<?= $id_table ?>" method="GET"
                    action="<?= URL::get(true, false, $feature['HTML']['href']) ?>">
                        <?php
                        foreach (explode('&', parse_url($feature['HTML']['href'], PHP_URL_QUERY)) as $param) {
                            list($name, $value) = explode('=', $param); ?>

                            <input type="hidden" name="<?= $name ?>" value="<?= $value ?>" />
                        <?php } ?>
                        <button type="submit"
                        <?php
                        if (!empty($feature['JS']['tooltip'])) {
                            echo 'data-tooltip="true" ';
                            echo implode(' ', array_map(function ($key, $value) {
                                return ("data-".$key.'="'.$value.'"');
                            }, array_keys($feature['JS']['tooltip']), $feature['JS']['tooltip']));
                        }
                        if (!empty($feature['JS']['confirmation'])) {
                            echo 'data-confirmation="true" ';
                            echo implode(' ', array_map(function ($key, $value) {
                                return ("data-".$key.'="'.$value.'"');
                            }, array_keys($feature['JS']['confirmation']), $feature['JS']['confirmation']));
                        }
                        if (!empty($feature['JS']['clipboard'])) {
                            echo implode(' ', array_map(function ($key, $value) {
                                return ("data-clipboard-".$key.'="'.$value.'"');
                            }, array_keys($feature['JS']['clipboard']), $feature['JS']['clipboard']));
                        } ?>
                        <?= (!empty($feature['HTML']['title']) ? 'title="'.$feature['HTML']['title'].'"' : '') ?>
                        class="<?= $feature['HTML']['class'] ?>">
                            <?php
                            if ($feature['HTML']['icon']) {
                                switch ($feature['HTML']['icon']['style'] ?? NULL) {
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
                                <i class="<?= $fa_class ?> fa-fw fa-<?= $feature['HTML']['icon']['name'] ?? $feature['HTML']['icon'] ?>" data-toggle="fa-<?= $feature['HTML']['icon']['name'] ?? $feature['HTML']['icon'] ?> fa-spinner"></i>
                            <?php } ?>
                            <?= $feature['HTML']['text'] ?>
                        </button>
                    </form>
                </div>
            <?php }

        return ob_get_clean();
    }
}
