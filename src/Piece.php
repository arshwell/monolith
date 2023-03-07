<?php

namespace ArshWell\Monolith;

/**
 * PHP Template Engin.

 * Allows you to set CSS and JS fields for every single page.
 * Also minifies them and HTML too.
 * You can 'hook' pieces on pages. These have their own CSS and JS, automatically added.

 * @package https://github.com/arshwell/monolith
*/
final class Piece {
    private static $pieces                  = array();
    private static $pieces_used_in_mails    = array();

    static function used (string $mail = NULL): array {
        return (!$mail ? self::$pieces : (self::$pieces_used_in_mails[$mail] ?? array()));
    }

    static function html (string $folder, array $piece = array()): string {
        do {
            // Looking if this piece is used inside a mail template.
            foreach (debug_backtrace(0) as $trace) {
                if (!empty($trace['class']) && $trace['class'] == 'ArshWell\Monolith\Mail'
                && !empty($trace['function']) && in_array($trace['function'], ['send', 'html'])) {
                    self::$pieces_used_in_mails[$trace['args'][0]][] = $folder;
                    break 2;
                }
            }

            self::$pieces[] = $folder;
        } while (false);

        ob_start();
            echo '<div class="arshpiece '. strtolower(str_replace('/', ' ', $folder)) .'">';
                $path = '';
                foreach (explode('/', $folder) as $f) {
                    $path .= ('/'. $f);

                    if (is_file('pieces'. $path .'/back.piece.php')) {
                        require('pieces'. $path .'/back.piece.php');
                    }
                }
                require('pieces/'. $folder .'/front.piece.php');
            echo '</div>';

        return ob_get_clean();
    }
}
