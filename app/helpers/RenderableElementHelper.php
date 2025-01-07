<?php

namespace App\Helpers;

/**
 * @deprecated
 */
class RenderableElementHelper {
    public static function implodeAndRender(string $separator, array $renderableElements) {
        $callbacks = [];

        foreach($renderableElements as $re) {
            $callbacks[] = function() use ($re) {
                return $re->render();
            };
        }

        return ArrayHelper::implodeCallbackArray($separator, $callbacks);
    }
}

?>