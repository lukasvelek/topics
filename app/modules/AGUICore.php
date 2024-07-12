<?php

namespace App\Modules;

abstract class AGUICore {
    /**
     * Creates a flash message and returns its HTML code
     * 
     * @param string $type Flash message type (info, success, warning, error)
     * @param string $text Flash message text
     * @param int $flashMessageCount Number of flash messages
     * @param bool $custom True if flash message has custom handler or false if not
     * @return string HTML code
     */
    protected function createFlashMessage(string $type, string $text, int $flashMessageCount, bool $custom = false) {
        $fmc = $flashMessageCount . '-' . ($custom ? '-custom' : '');
        $removeLink = '<p class="fm-text fm-link" style="cursor: pointer" onclick="closeFlashMessage(\'fm-' . $fmc . '\')">&times;</p>';

        $jsAutoRemoveScript = '<script type="text/javascript">autoHideFlashMessage(\'fm-' . $fmc . '\')</script>';

        $code = '<div id="fm-' . $fmc . '" class="row fm-' . $type . '"><div class="col-md"><p class="fm-text">' . $text . '</p></div><div class="col-md-1" id="right">' . ($custom ? '' : $removeLink) . '</div><div id="fm-' . $fmc . '-progress-bar" style="position: absolute; left: 0; bottom: 1%; border-bottom: 2px solid black"></div>' . ($custom ? '' : $jsAutoRemoveScript) . '</div>';

        return $code;
    }
}

?>