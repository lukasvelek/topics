<?php

namespace App\Helpers;

use App\Exceptions\CallbackExecutionException;
use Exception;

class ArrayHelper {
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
}

?>