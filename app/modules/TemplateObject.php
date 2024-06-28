<?php

namespace App\Modules;

use App\UI\FormBuilder\FormBuilder;
use App\UI\IRenderable;

class TemplateObject {
    private string $__templateContent;
    private array $__values;

    public function __construct(string $templateContent) {
        $this->__templateContent = $templateContent;
        $this->__values = [];
    }

    public function __set(string $name, mixed $value) {
        $this->$name = $value;
        $this->__values[] = $name;
    }

    public function __get(string $name) {
        return $this->$name;
    }

    public function render() {
        foreach($this->__values as $__value) {
            if($this->$__value === null) {
                continue;
            }

            $upperValue = strtoupper($__value);

            if($this->$__value instanceof TemplateObject) {
                $this->$__value->render();
                $this->$__value = $this->$__value->getRenderedContent();
            } else if($this->$__value instanceof FormBuilder) {
                $action = $this->$__value->getAction();
                $action['isFormSubmit'] = '1';
                $this->$__value->setAction($action);
                $this->$__value = $this->$__value->render();
            } else if($this->$__value instanceof IRenderable) {
                $this->$__value = $this->$__value->render();
            }

            $this->replace($upperValue, $this->$__value, $this->__templateContent);
        }

        return $this;
    }

    public function replace(string $key, string|array $value, string $object) {
        if($value === null) {
            return false;
        }

        if(is_array($value)) {
            $tmp = '';

            foreach($value as $v) {
                $tmp .= $v;
            }

            $value = $tmp;
        }
        
        $this->__templateContent = str_replace('$' . $key . '$', $value, $object);
    }

    public function getRenderedContent() {
        return $this->__templateContent;
    }

    public function getValues() {
        return $this->__values;
    }

    public function join(TemplateObject $extendingObject) {
        $extendingValues = $extendingObject->getValues();

        foreach($extendingValues as $ev) {
            $this->replace($ev, $extendingObject->$ev, $this->__templateContent);
            $this->$ev = $extendingObject->$ev;
        }
    }
}

?>