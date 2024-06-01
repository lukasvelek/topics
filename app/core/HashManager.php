<?php

namespace App\Core;

class HashManager {
    public static function createHash(int $length = 32, bool $special = true) {
        $alph = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $special = '_.,?@-!';

        if($special === true) {
            $alph .= $special;
        }

        $hash = '';

        for($i = 0; $i < $length; $i++) {
            $r = rand(0, strlen($alph) - 1);

            $hash .= $alph[$r];
        }

        return $hash;
    }
}

?>