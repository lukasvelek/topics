<?php

namespace App\UI\ModalBuilder;

use App\Core\Http\HttpRequest;
use App\UI\AComponent;
use App\UI\FormBuilder\FormBuilder;

class ModalBuilder extends AComponent {
    private string $id;
    private string $title;
    private string $content;
    protected array $scripts;

    public function __construct(HttpRequest $httpRequest, array $cfg) {
        parent::__construct($httpRequest, $cfg);

        $this->id = 'modal-inner';
        $this->title = 'Modal';
        $this->content = 'Modal content';
        $this->scripts = [];
    }

    public function setId(string $id) {
        $this->id = $id;
    }

    public function setTitle(string $title) {
        $this->title = $title;
    }

    public function setContentFromFormBuilder(FormBuilder $fb) {
        $this->content = $fb->render();
    }

    public function render() {
        $template = $this->getTemplate(__DIR__ . '/modal.html');

        $template->modal_id = $this->id;
        $template->modal_title = $this->title;
        $template->modal_content = $this->content;
        $template->modal_close_button = $this->createCloseButton();
        $template->scripts = $this->createScripts();

        return $template->render()->getRenderedContent();
    }

    private function createCloseButton() {
        return '<a class="grid-link" href="#" onclick="' . $this->componentName . '_closeModal();">&times;</a>';
    }

    private function createScripts() {
        $this->scripts[] = '
            <script type="text/javascript">
                function ' . $this->componentName . '_closeModal() {
                   $("#' . $this->id . '-modal-inner").css("visibility", "hidden")
                        .css("height", "0px");
                }
            </script>
        ';

        return implode('', $this->scripts);
    }

    public static function createFromComponent(AComponent $component) {
        $obj = new self($component->httpRequest, $component->cfg);
        $obj->setApplication($component->app);
        $obj->setPresenter($component->presenter);

        return $obj;
    }
}

?>