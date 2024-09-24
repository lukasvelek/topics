<?php

namespace App\Helpers;

/**
 * LinkHelper class contains useful methods for link creation, usage or formatting
 * 
 * @author Lukas Velek
 */
class LinkHelper {
    /**
     * Creates a URL link from given parameters
     * 
     * @param array $params
     * @return string URL link
     */
    public static function createUrlFromArray(array $params) {
        $url = '?';

        $tmp = [];

        foreach($params as $key => $value) {
            $tmp[] = $key . '=' . $value;
        }

        $url .= implode('&', $tmp);

        return $url;
    }
}

?>