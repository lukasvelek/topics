<?php

namespace App\Core;

use App\Modules\AModule;

class RenderEngine {
    private AModule $module;

    private string $presenterTitle;
    private string $actionTitle;

    public function __construct(AModule $module, string $presenter, string $action) {
        $this->module = $module;
        $this->presenterTitle = $presenter;
        $this->actionTitle = $action;
    }

    public function render(bool $isAjax) {
        $this->beforeRender();

        return $this->module->render($this->presenterTitle, $this->actionTitle, $isAjax);
    }
    
    private function beforeRender() {
        $this->module->loadPresenters();
    }
}

?>