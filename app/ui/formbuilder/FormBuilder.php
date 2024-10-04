<?php

namespace App\UI\FormBuilder;

use App\Core\HashManager;
use App\UI\IRenderable;

class FormBuilder implements IFormRenderable {
    private array $handlerUrl;
    private string $method;
    private array $elements;
    private bool $isInSection;
    private ?Section $currentSection;
    private string $name;
    private bool $canHaveFiles;

    public function __construct() {
        $this->handlerUrl = [];
        $this->method = 'POST';
        $this->elements = [];
        $this->isInSection = false;
        $this->currentSection = null;
        $this->name = 'form_' . HashManager::createHash(8, false);
        $this->canHaveFiles = false;
    }

    public function getName() {
        return $this->name;
    }

    public function getTagName() {}

    public function updateElement(string $name, callable $updateOperation) {
        foreach($this->elements as $k => $element) {
            if($element instanceof ElementDuo) {
                $label = $element->getLabel();
                $el = $element->getElement();

                if($label instanceof IFormRenderable && $label->getName() == $name) {
                    $label = $updateOperation($label);
                    $element->setLabel($label);
                    $this->elements[$k] = $element;
                    break;
                } else if($el instanceof IFormRenderable && $el->getName() == $name) {
                    $el = $updateOperation($el);
                    $element->setElement($el);
                    $this->elements[$k] = $element;
                    break;
                }
            } else {
                if($element instanceof IFormRenderable && $element->getName() == $name) {
                    $element = $updateOperation($element);
                    $this->elements[$k] = $element;
                    break;
                }
            }
        }
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
        $this->handlerUrl = $action;

        return $this;
    }

    public function getAction() {
        return $this->handlerUrl;
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

    public function addEmailInput(string $name, ?string $label = null, mixed $value = null, bool $required = false) {
        $ei = new EmailInput($name, $value);

        $ei->setRequired($required);

        if($label !== null) {
            $ei = new ElementDuo($ei, new Label($label, $name, $required), $name);
        }

        $this->addElement($name, $ei);

        return $this;
    }

    public function addSelect(string $name, ?string $label = null, array $options = [], bool $required = false) {
        $s = new Select($name, $options);

        $s->setRequired($required);

        if($label !== null) {
            $s = new ElementDuo($s, new Label($label, $name, $required), $name);
        }

        $this->addElement($name, $s);

        return $this;
    }

    public function addSubmit(string $text = 'Submit', bool $disabled = false, bool $center = false, string $name = 'btn_submit') {
        $sb = new SubmitButton($text, $disabled, $name);

        if($center) {
            $sb->setCenter($center);
        }

        $this->addElement($name, $sb);

        return $this;
    }

    public function addMultipleSubmitButtons(array $submitButtons) {
        $code = '';

        foreach($submitButtons as $sb) {
            $code .= $sb->render() . '<br>';
        }

        $this->elements['submit'] = $code;
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

    public function addButton(string $text, string $onclickAction, string $id = '') {
        $b = new Button($text, $onclickAction);
        $b->id = $id;

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

    public function addRadios(string $name, ?string $label = null, array $choices = [], mixed $value = null, bool $required = true) {
        $rig = new RadioInputGroup($name);

        foreach($choices as $choiceKey => $choiceText) {
            $ri = new RadioInput($name, $choiceKey, $choiceText);

            if($choiceKey == $value) {
                $ri->setChecked();
            }

            $rig->addRadio($ri);
        }

        if($label !== null) {
            $rig = new ElementDuo($rig, new Label($label, $name, $required), $name);
        }

        $this->addElement($name, $rig);

        return $this;
    }

    public function addJSHandler(string $handlerLink) {
        $this->elements['js_handler'] = '<script type="text/javascript" src="' . $handlerLink . '"></script>';

        return $this;
    }

    public function addHidden(string $name, mixed $value) {
        $hi = new HiddenInput($name, $value);

        $this->addElement($name, $hi);

        return $this;
    }

    public function setCanHaveFiles(bool $canHaveFiles = true) {
        $this->canHaveFiles = $canHaveFiles;

        return $this;
    }

    public function addFileInput(string $name, ?string $label = null) {
        $fi = new FileInput($name);

        if($label !== null) {
            $fi = new ElementDuo($fi, new Label($label, $name), $name);
        }

        $this->addElement($name, $fi);

        return $this;
    }

    public function render() {
        $tmp = [];
        foreach($this->handlerUrl as $k => $v) {
            $tmp[] = $k . '=' . $v;
        }

        $url = '?' . implode('&', $tmp);

        $code = '<form action="' . $url . '" method="' . $this->method . '" data-formname="' . $this->name . '"';

        if($this->canHaveFiles) {
            $code .= ' enctype="multipart/form-data"';
        }

        $code .= '>';

        $i = 0;
        foreach($this->elements as $element) {
            if($element instanceof Section) {
                $code .= $element->render();
            } else if($element instanceof HiddenInput) {
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