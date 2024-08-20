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
            throw new CallbackExecutionException($e, $e);
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
}

?>