<?php

namespace App\Exceptions;

use Throwable;

class TemplateDoesNotExistException extends AException {
    public function __construct(string $templateName, ?string $templatePath = null, ?Throwable $previous = null) {
        parent::__construct('TemplateDoesNotExistException', $this->toString($templateName, $templatePath), $previous);
    }

    private function toString(string $templateName, ?string $templatePath = null) {
        if($templatePath !== null) {
            return sprintf('Template \'%s\' not found in \'%s\'!', $templateName, $templatePath);
        } else {
            return sprintf('Template \'%s\' does not exist!', $templateName, $templatePath);
        }
    }
}

?>