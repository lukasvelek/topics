<?php

namespace App\UI\FormBuilder;

use App\UI\IRenderable;

class FormBuilder implements IRenderable {
    private string $handlerUrl;
    private string $method;
    private array $elements;

    public function __construct() {
        $this->handlerUrl = '';
        $this->method = 'POST';
        $this->elements = [];
    }

    public function setMethod(string $method = 'POST') {
        $this->method = $method;

        return $this;
    }

    public function setAction(array $action) {
        $tmp = [];

        foreach($action as $k => $v) {
            $tmp[] = $k . '=' . $v;
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
            $ti = new ElementDuo($ti, new Label($label, $name, $required));
        }

        $this->addElement($name, $ti);

        return $this;
    }

    public function addSelect(string $name, ?string $label = null, array $options = []) {
        $s = new Select($name, $options);

        if($label !== null) {
            $s = new ElementDuo($s, new Label($label, $name));
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
            $pi = new ElementDuo($pi, new Label($label, $name, $required));
        }

        $this->addElement($name, $pi);

        return $this;
    }

    public function addTextArea(string $name, ?string $label = null, mixed $value = null, bool $required = false, int $rows = 3) {
        $ta = new TextArea($name, $value);

        $ta->setRows($rows);
        $ta->setRequired($required);

        if($label !== null) {
            $ta = new ElementDuo($ta, new Label($label, $name, $required));
        }

        $this->addElement($name, $ta);

        return $this;
    }

    public function addElement(string $name, IRenderable $object) {
        $this->elements[$name] = $object;
    }

    public function render() {
        $code = '<form action="' . $this->handlerUrl . '" method="' . $this->method . '">';

        $elementCodes = [];

        foreach($this->elements as $element) {
            $elementCodes[] = $element->render();
        }

        $code .= implode('<br><br>', $elementCodes);

        $code .= '</form>';

        return $code;
    }
}

?>