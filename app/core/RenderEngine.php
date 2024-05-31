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

    public function render() {
        $this->beforeRender();

        return $this->module->renderPresenter($this->presenterTitle, $this->actionTitle);
    }
    
    private function beforeRender() {
        $this->module->loadPresenters();
    }
}

?>