<?php

namespace App\Helpers;

use App\Exceptions\CallbackExecutionException;
use Exception;

/**
 * ArrayHelper contains useful functions for working with arrays
 * 
 * @author Lukas Velek
 */
class ArrayHelper {
    /**
     * Creates an array from callbacks (runs every callback passed in $callbacks and saves it's value to an array) and then implodes it with given $separator and returns it.
     * 
     * @param string $separator Implode separator character
     * @param array $callbacks Callbacks which values create the resulting array
     * @return string Imploded array
     */
    public static function implodeCallbackArray(string $separator, array $callbacks) {
        $values = [];

        try {
            foreach($callbacks as $callback) {
                $values[] = $callback();
            }
        } catch (Exception $e) {
            throw new CallbackExecutionException($e, [], $e);
        }

        return implode($separator, $values);
    }

    /**
     * Checks if given array of keys all exist in given array to check
     * 
     * @param array $keys Keys which existence should be checked
     * @param array $arrayToCheck Array which will be used for checking
     * @return bool True if all keys exist in the array or false if not
     */
    public static function checkArrayKeysExistInArray(array $keys, array $arrayToCheck) {
        $ok = true;

        foreach($keys as $key) {
            if(!array_key_exists($key, $arrayToCheck)) {
                $ok = false;
                break;
            }
        }

        return $ok;
    }

    /**
     * Clones an array
     * 
     * @param array $array Array to be cloned
     * @return array Cloned array
     */
    public static function cloneArray(array $array) {
        $tmp = [];

        foreach($array as $k => $v) {
            if(is_object($k)) {
                $k = clone $k;
            }
            if(is_object($v)) {
                $v = clone $v;
            }
            if(is_array($k)) {
                $k = self::cloneArray($k);
            }
            if(is_array($v)) {
                $v = self::cloneArray($v);
            }
            $tmp[$k] = $v;
        }

        return $tmp;
    }

    /**
     * Reverses given array
     * 
     * @param array $array Array to reverse
     * @return array Reversed array
     */
    public static function reverseArray(array $array) {
        $tmp = [];
        foreach($array as $k => $v) {
            $tmp = array_merge([$k => $v], $tmp);
        }

        return $tmp;
    }
}

?>