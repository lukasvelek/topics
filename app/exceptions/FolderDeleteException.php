<?php

namespace App\Exceptions;

use Throwable;

class FolderDeleteException extends AException {
    public function __construct(string $message, string $folderName, ?Throwable $previous = null) {
        parent::__construct('FolderDeleteException', $message . ' ' . $folderName, $previous);
    }
}

?>