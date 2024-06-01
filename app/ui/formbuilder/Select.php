<?php

namespace App\UI\FormBuilder;

use App\UI\IRenderable;

class Select extends AElement {
    public function __construct(string $name, array $options = []) {
        $content = $this->processOptions($options);
        
        parent::__construct('select', $content);

        $this->name = $name;
    }

    private function processOptions(array $options = []) {
        $code = null;
        
        if(!empty($options)) {
            $code = '';
            
            foreach($options as $option) {
                if($option instanceof Option) {
                    $code .= $option->render();
                } else {
                    $value = $option['value'];
                    $text = $option['text'];
                    $selected = array_key_exists('selected', $option);

                    $o = new Option($value, $text, $selected);

                    $code .= $o->render();
                }
            }
        }

        return $code;
    }
}

?>