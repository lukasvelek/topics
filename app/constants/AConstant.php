<?php

namespace App\Constants;

abstract class AConstant implements IToStringConstant {
    abstract static function getAll(): array;
}

?>