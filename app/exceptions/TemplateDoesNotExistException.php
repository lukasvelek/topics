<?php

namespace App\Exceptions;

use Throwable;

class TemplateDoesNotExistException extends AException {
    public function __construct(string $templateName, string $templatePath, ?Throwable $previous = null) {
        parent::__construct($this->toString($templateName, $templatePath), $previous);
    }

    private function toString(string $templateName, string $templatePath) {
        return sprintf('Template \'%s\' not found in \'%s\'!', $templateName, $templatePath);
    }
}

?>