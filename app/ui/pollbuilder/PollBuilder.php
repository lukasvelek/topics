<?php

namespace App\UI\PollBuilder;

use App\UI\FormBuilder\FormBuilder;
use App\UI\IRenderable;
use App\UI\LinkBuilder;

class PollBuilder implements IRenderable {
    private array $choices;
    private string $title;
    private string $description;
    private array $handlerUrl;

    public function __construct() {
        $this->choices = [];
        $this->title = 'My poll';
        $this->description = 'Description of my poll';
        $this->handlerUrl = [];
    }

    public function setTitle(string $title) {
        $this->title = $title;
        return $this;
    }

    public function setDescription(string $description) {
        $this->description = $description;
        return $this;
    }

    /**
     * Choice value => choice text
     * 
     * E.g.: ['pizza' => 'Pizza', 'spaghetti' => 'Spaghetti']
     */
    public function setChoices(array $choices) {
        $this->choices = $choices;
        return $this;
    }

    public function setHandlerUrl(array $handlerUrl) {
        $this->handlerUrl = $handlerUrl;

        return $this;
    }

    public function render() {
        $form = $this->build();

        $code = '
            <div class="row">
                <div class="col-md" id="center">
                    <p class="post-title">' . $this->title . '</p>
                    <p class="post-data">' . $this->description . '</p>
                </div>
            </div>
            <div class="row">
                <div class="col-md" id="form">
                    ' . $form . '
                </div>
            </div>
        ';

        return $code;
    }

    private function build() {
        $fb = new FormBuilder();

        $fb ->setAction($this->handlerUrl)
            ->setMethod('POST')
            ->addRadios('choice', 'Choose:', $this->choices, null, true)
        ;

        return $fb->render();
    }
}

?>