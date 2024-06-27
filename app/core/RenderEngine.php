<?php

namespace App\Core;

use App\Modules\AModule;

/**
 * Render engine class that takes care of page rendering
 * 
 * @author Lukas Velek
 */
class RenderEngine {
    private AModule $module;

    private string $presenterTitle;
    private string $actionTitle;

    /**
     * Class constructor
     * 
     * @param AModule $module Module class instance that extends AModule
     * @param string $presenter Presenter name
     * @param string $action Action name
     */
    public function __construct(AModule $module, string $presenter, string $action) {
        $this->module = $module;
        $this->presenterTitle = $presenter;
        $this->actionTitle = $action;
    }

    /**
     * Renders the page content by rendering
     * 
     * @param bool $isAjax Is the request called from AJAX?
     * @param string HTML page code
     */
    public function render(bool $isAjax) {
        $this->beforeRender();

        return $this->module->render($this->presenterTitle, $this->actionTitle, $isAjax);
    }
    
    /**
     * Loads all presenters in a given module
     */
    private function beforeRender() {
        $this->module->loadPresenters();
    }
}

?>