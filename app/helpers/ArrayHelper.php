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
}

?>