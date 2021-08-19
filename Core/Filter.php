<?php

namespace Arsh\Core;

/**
 * Core class for backend programming which has rutine functions.

 * @package Arsh/Core
 * @author Valentin Arșavin <valentin@iscreambrands.ro>
*/
final class Filter {
    static function isURL (string $url): bool {
        return (filter_var($url, FILTER_VALIDATE_URL) === FALSE ? false : true);
    }

    static function isEmail (string $email): bool {
        return (filter_var($email, FILTER_VALIDATE_EMAIL) === FALSE ? false : true);
    }

    static function isIP (string $ip): bool {
        return (filter_var($ip, FILTER_VALIDATE_IP) === FALSE ? false : true);
    }

    static function isCNP (string $cnp): bool {
        // CNP must have 13 characters
        if (strlen($cnp) != 13) {
            return false;
        }
        $cnp = str_split($cnp);

        $hashTable = array(2, 7, 9, 1, 4, 6, 3, 5, 8, 2, 7, 9);
        $hashResult = 0;

        // All characters must be numeric
        for ($i=0; $i<13; $i++) {
            if (!is_numeric($cnp[$i])) {
                return false;
            }
            $cnp[$i] = (int)$cnp[$i];
            if ($i < 12) {
                $hashResult += (int)$cnp[$i] * (int)$hashTable[$i];
            }
        }
        unset($hashTable, $i);

        $hashResult = $hashResult % 11;
        if ($hashResult == 10) {
            $hashResult = 1;
        }

        // Check Year
        $year = ($cnp[1] * 10) + $cnp[2];
        switch ($cnp[0]) {
            case 1:
            case 2: { // citizens born between 1 Jan 1900 and 31 Dec 1999
                $year += 1900;
            }
            break;
            case 3:
            case 4: { // citizens born between 1 Jan 1800 and 31 Dec 1899
                $year += 1800;
            }
            break;
            case 5:
            case 6: { // citizens born between 1 Jan 2000 and 31 Dec 2099
                $year += 2000;
            }
            break;
            case 7:
            case 8:
            case 9: { // residents and foreign citizens
                $year += 2000;
                if($year > (int)date('Y')-14) {
                    $year -= 100;
                }
            }
            break;
            default: {
                return false;
            }
            break;
        }

        return ($year > 1800 && $year < 2099 && $cnp[12] == $hashResult);
    }
}
