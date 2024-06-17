<?php

namespace App\UI\FormBuilder;

use App\Core\HashManager;
use App\UI\IRenderable;

class FormBuilder implements IRenderable {
    private string $handlerUrl;
    private string $method;
    private array $elements;
    private bool $isInSection;
    private ?Section $currentSection;

    public function __construct() {
        $this->handlerUrl = '';
        $this->method = 'POST';
        $this->elements = [];
        $this->isInSection = false;
        $this->currentSection = null;
    }

    public function startSection(string $name, bool $isHiddenInDefault = false) {
        $section = new Section($name);
        $section->setHidden($isHiddenInDefault);
        $this->isInSection = true;

        if($this->currentSection !== null) {
            $section->parentSection = $this->currentSection;
        }

        $this->currentSection = $section;

        return $this;
    }

    public function endSection() {
        $this->elements[$this->currentSection->name] = $this->currentSection;

        if($this->currentSection->parentSection !== null) {
            $this->currentSection = $this->currentSection->parentSection;
        } else {
            $this->isInSection = false;
            $this->currentSection = null;
        }

        return $this;
    }

    public function setMethod(string $method = 'POST') {
        $this->method = $method;

        return $this;
    }

    public function setAction(array $action) {
        $tmp = [];

        foreach($action as $k => $v) {
            if($v !== null) {
                $tmp[] = $k . '=' . $v;
            }
        }

        $url = '?' . implode('&', $tmp);

        $this->handlerUrl = $url;

        return $this;
    }

    public function addLabel(string $text, ?string $for = null) {
        $this->addElement('lbl_' . (is_null($for) ? '' : $for), new Label($text, $for));

        return $this;
    }

    public function addTextInput(string $name, ?string $label = null, mixed $value = null, bool $required = false) {
        $ti = new TextInput($name, $value);

        $ti->setRequired($required);

        if($label !== null) {
            $ti = new ElementDuo($ti, new Label($label, $name, $required), $name);
        }

        $this->addElement($name, $ti);

        return $this;
    }

    public function addSelect(string $name, ?string $label = null, array $options = [], bool $required = false) {
        $s = new Select($name, $options);

        if($label !== null) {
            $s = new ElementDuo($s, new Label($label, $name, $required), $name);
        }

        $this->addElement($name, $s);

        return $this;
    }

    public function addSubmit(string $text = 'Submit') {
        $sb = new SubmitButton($text);

        $this->addElement('btn_submit', $sb);

        return $this;
    }

    public function addPassword(string $name, ?string $label = null, mixed $value = null, bool $required = false) {
        $pi = new PasswordInput($name, $value);

        $pi->setRequired($required);

        if($label !== null) {
            $pi = new ElementDuo($pi, new Label($label, $name, $required), $name);
        }

        $this->addElement($name, $pi);

        return $this;
    }

    public function addTextArea(string $name, ?string $label = null, mixed $value = null, bool $required = false, int $rows = 3) {
        $ta = new TextArea($name, $value);

        $ta->setRows($rows);
        $ta->setRequired($required);

        if($label !== null) {
            $ta = new ElementDuo($ta, new Label($label, $name, $required), $name);
        }

        $this->addElement($name, $ta);

        return $this;
    }

    public function addElement(string $name, IRenderable $object) {
        if($this->isInSection) {
            $this->currentSection->addElement($name, $object);
        } else {    
            $this->elements[$name] = $object;
        }

        return $this;
    }

    public function addButton(string $text, string $onclickAction) {
        $b = new Button($text, $onclickAction);

        $this->elements['btn_' . HashManager::createHash()] = $b;

        return $this;
    }

    public function addCheckbox(string $name, ?string $label = null, bool $checked = false) {
        $ci = new CheckboxInput($name, $checked);

        if($label !== null) {
            $ci = new ElementDuo($ci, new Label($label, $name), $name);
        }

        $this->addElement($name, $ci);

        return $this;
    }

    public function addDatetime(string $name, ?string $label = null, ?string $value = null, bool $required = false) {
        $di = new DateTimeInput($name, $value);

        $di->setRequired($required);

        if($label !== null) {
            $di = new ElementDuo($di, new Label($label, $name, $required), $name);
        }

        $this->addElement($name, $di);

        return $this;
    }

    public function addJSHandler(string $handlerLink) {
        $this->elements['js_handler'] = '<script type="text/javascript" src="' . $handlerLink . '"></script>';
    }

    public function render() {
        $code = '<form action="' . $this->handlerUrl . '" method="' . $this->method . '">';

        $i = 0;
        foreach($this->elements as $element) {
            if($element instanceof Section) {
                $code .= $element->render();
            } else if($element instanceof IRenderable) {
                if(($i + 1) == count($this->elements)) {
                    $code .= $element->render();
                } else {
                    $code .= $element->render() . '<br><br>';
                }
            } else {
                $code .= $element;
            }

            $i++;
        }

        $code .= '</form>';

        return $code;
    }
}

?>