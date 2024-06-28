<?php

namespace App\Core;

use App\Exceptions\GeneralException;
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

    private array $cachedPages;

    private ?string $renderedContent;

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
        $this->cachedPages = [];
        $this->renderedContent = null;
    }

    /**
     * Renders the page content by rendering
     * 
     * @param bool $isAjax Is the request called from AJAX?
     * @param string HTML page code
     */
    public function render(bool $isAjax) {
        $this->beforeRender();

        if($this->renderedContent === null) {
            $isCacheable = false;

            [$this->renderedContent, $isCacheable] = $this->module->render($this->presenterTitle, $this->actionTitle, $isAjax);

            if($isCacheable) {
                $this->cachePage();
            }
        }

        return $this->renderedContent;
    }
    
    /**
     * Loads all presenters in a given module
     */
    private function beforeRender() {
        $this->module->loadPresenters();
        $this->loadCachedPages();

        $key = $this->module->getTitle() . '_' . $this->presenterTitle;

        if(array_key_exists($key, $this->cachedPages)) {
            $this->renderedContent = $this->cachedPages[$key];
        }
    }

    private function loadCachedPages() {
        $result = CacheManager::loadPagesFromCache();
        
        if($result !== false && $result !== null) {
            $this->cachedPages = $result;
        } else {
            $this->cachedPages = [];
        }
    }

    private function cachePage() {
        CacheManager::savePageToCache($this->module->getTitle(), $this->presenterTitle, $this->renderedContent);
    }
}

?>