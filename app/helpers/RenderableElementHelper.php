<?php

namespace App\Helpers;

use App\UI\IRenderable;

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