<?php

namespace App\Core;

/**
 * HashManager allows creating hashes and hashing passwords
 * 
 * @author Lukas Velek
 */
class HashManager {
    /**
     * Creates a hash
     * 
     * @param int $length Hash length
     * @param bool $special Can the hash contain special symbols
     * @return string Generated hash
     */
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

    /**
     * Hashes password
     * 
     * @param string $password Password to hash
     * @return string Hashed password
     */
    public static function hashPassword(string $password) {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Creates a hash that is a entity ID
     * 
     * @return string Generated entity ID
     */
    public static function createEntityId() {
        return self::createHash(32, false);
    }
}

?>