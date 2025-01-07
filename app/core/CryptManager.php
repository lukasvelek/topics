<?php

namespace App\Core;

/**
 * CryptManager is responsible for encryption and decryption
 * 
 * @author Lukas Velek
 */
class CryptManager {
    private string $text;

    /**
     * Class constructor
     * 
     * @param string $text Text to process
     */
    private function __construct(string $text) {
        $this->text = $text;
    }

    /**
     * Encrypts the given text
     */
    private function internalEncrypt() {
        for($i = 0; $i < 16; $i++) {
            $this->text = base64_encode($this->text);
        }
    }

    /**
     * Decrypts the given text
     */
    private function internalDecrypt() {
        for($i = 0; $i < 16; $i++) {
            $this->text = base64_decode($this->text);
        }
    }

    /**
     * Encrypts given text
     * 
     * @param string $text Text to encrypt
     * @return string Encrypted text
     */
    public static function encrypt(string $text) {
        $obj = new self($text);
        $obj->internalEncrypt();
        return $obj->text;
    }

    /**
     * Decrypts given text
     * 
     * @param string $text Text to decrypt
     * @return string Decrypted text
     */
    public static function decrypt(string $text) {
        $obj = new self($text);
        $obj->internalDecrypt();
        return $obj->text;
    }
}

?>