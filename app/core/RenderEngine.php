<?php

namespace App\Core;

use App\Logger\Logger;
use App\Modules\AModule;

/**
 * Render engine class that takes care of page rendering
 * 
 * @author Lukas Velek
 */
class RenderEngine {
    private AModule $module;
    private Logger $logger;

    private string $presenterTitle;
    private string $actionTitle;

    private ?string $renderedContent;

    private bool $isAjax;

    /**
     * Class constructor
     * 
     * @param AModule $module Module class instance that extends AModule
     * @param string $presenter Presenter name
     * @param string $action Action name
     */
    public function __construct(Logger $logger, AModule $module, string $presenter, string $action) {
        $this->logger = $logger;
        $this->module = $module;
        $this->presenterTitle = $presenter;
        $this->actionTitle = $action;
        $this->renderedContent = null;
        $this->isAjax = false;
    }

    /**
     * Does the call come from AJAX?
     * 
     * @param bool $isAjax Is AJAX?
     */
    public function setAjax(bool $isAjax = true) {
        $this->isAjax = $isAjax;
    }

    /**
     * Renders the page content by rendering
     * 
     * @return string HTML page code or null
     */
    public function render() {
        $this->beforeRender();

        if($this->renderedContent === null) {
            $this->renderedContent = $this->module->render($this->presenterTitle, $this->actionTitle);
        }

        return $this->renderedContent;
    }
    
    /**
     * Loads all presenters in a given module
     */
    private function beforeRender() {
        $this->module->loadPresenters();

        $key = $this->module->getTitle() . '_' . $this->presenterTitle;

        $this->module->setAjax($this->isAjax);
    }
}

?>