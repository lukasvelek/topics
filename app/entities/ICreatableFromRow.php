<?php

namespace App\Entities;

interface ICreatableFromRow {
    static function createEntityFromDbRow(mixed $row);
}

?>