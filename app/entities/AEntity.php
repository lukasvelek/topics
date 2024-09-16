<?php

namespace App\Entities;

use App\Helpers\ValueHelper;

abstract class AEntity implements ICreatableFromRow {
    protected function checkInt(mixed $value) {
        return ValueHelper::isValueInteger($value);
    }

    protected function checkIntOrNull(mixed $value) {
        return ValueHelper::isValueInteger($value) || ValueHelper::isValueNull($value);
    }

    protected function checkString(mixed $value) {
        return ValueHelper::isValueString($value);
    }

    protected function checkStringOrNull(mixed $value) {
        return ValueHelper::isValueString($value) || ValueHelper::isValueNull($value);
    }

    protected function checkBool(mixed $value) {
        return ValueHelper::isValueBool($value);
    }

    protected function checkDouble(mixed $value) {
        return ValueHelper::isValueDouble($value);
    }

    protected function checkDoubleOrNull(mixed $value) {
        return ValueHelper::isValueDouble($value) || ValueHelper::isValueNull($value);
    }
}

?>