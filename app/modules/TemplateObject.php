<?php

namespace App\Modules;

use App\UI\FormBuilder\FormBuilder;
use App\UI\IRenderable;

/**
 * Class that defines a web template
 * 
 * @author Lukas Velek
 */
class TemplateObject {
    private const SHORTEN_CODE = true;

    private string $__templateContent;
    private array $__values;

    /**
     * Class constructor
     * 
     * @param string $templateContent Template HTML code
     */
    public function __construct(string $templateContent) {
        $this->__templateContent = $templateContent;
        $this->__values = [];
    }

    /**
     * Sets a new parameter that will be then used to fill a macro in the template's code
     * 
     * @param string $name Parameter name (same as the macro name in the template)
     * @param mixed $valye Parameter value
     */
    public function __set(string $name, mixed $value) {
        $this->$name = $value;
        $this->__values[] = $name;
    }

    /**
     * Gets a parameter with the defined name
     * 
     * @param string $name Parameter name
     * @return mixed Parameter value
     */
    public function __get(string $name) {
        return $this->$name;
    }

    /**
     * Fills the template macros, renders and builds all components and finally renders the template HTML code
     * 
     * @return self
     */
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

            $this->replace($upperValue, $this->$__value);
        }

        if(self::SHORTEN_CODE) {
            $lines = [];
            foreach(explode("\r\n", $this->__templateContent) as $line) {
                $lines[] = trim($line);
            }
        
            $this->__templateContent = implode('', $lines);
        }

        return $this;
    }

    /**
     * Replaces macro in the template with given value or value array. If a value array is given, then it implodes the values together and places them after each other.
     * 
     * @param string $key Macro name
     * @param string|array $value Value or value array
     * @param string $object HTML code
     */
    public function replace(string $key, string|array $value) {
        if($value === null) {
            return false;
        }

        if(is_array($value)) {
            $value = implode('', $value);
        }
        
        $this->__templateContent = str_replace('$' . $key . '$', $value, $this->__templateContent);
    }

    /**
     * Returns the renderd HTML code
     * 
     * @return string HTML code
     */
    public function getRenderedContent() {
        return $this->__templateContent;
    }

    /**
     * Returns the parameter names
     * 
     * @return array Parameter names
     */
    public function getValues() {
        return $this->__values;
    }

    /**
     * Joins this and some other TemplateObject instance together
     * 
     * @param TemplateObject $extendingObject TemplateObject instance
     */
    public function join(TemplateObject $extendingObject) {
        $extendingValues = $extendingObject->getValues();

        foreach($extendingValues as $ev) {
            $this->replace($ev, $extendingObject->$ev);
            $this->$ev = $extendingObject->$ev;
        }
    }
}

?>