<?php

namespace App\Helpers;

class ColorHelper {
    public static function createColorCombination() {
        $fg_r = rand(0, 255) - 100;
        $fg_g = rand(0, 255) - 100;
        $fg_b = rand(0, 255) - 100;

        if($fg_r < 0) {
            $fg_r = 0;
        }
        if($fg_g < 0) {
            $fg_g = 0;
        }
        if($fg_b < 0) {
            $fg_b = 0;
        }

        $t = rand(0, 50);

        $bg_r = $fg_r + 100 + $t;
        $bg_g = $fg_g + 100 + $t;
        $bg_b = $fg_b + 100 + $t;


        if($bg_r > 255) {
            $bg_r = 255;
        } else if($bg_r == 100) {
            $bg_r += 50;
        }
        if($bg_g > 255) {
            $bg_g = 255;
        } else if($bg_g == 100) {
            $bg_g += 50;
        }
        if($bg_b > 255) {
            $bg_b = 255;
        } else if($bg_b == 100) {
            $bg_b += 50;
        }

        return ['rgb(' . $fg_r . ', ' . $fg_g . ', ' . $fg_b . ')', 'rgb(' . $bg_r . ', ' . $bg_g . ', ' . $bg_b . ')'];
    }
}

?>