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

    private array $cachedPages;

    private ?string $renderedContent;

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

    /**
     * Loads cached pages from cache
     */
    private function loadCachedPages() {
        $cm = new CacheManager($this->logger);
        $result = $cm->loadPagesFromCache();
        
        if($result !== false && $result !== null) {
            $this->cachedPages = $result;
        } else {
            $this->cachedPages = [];
        }
    }

    /**
     * Saves page to cache
     */
    private function cachePage() {
        $cm = new CacheManager($this->logger);
        $cm->savePageToCache($this->module->getTitle(), $this->presenterTitle, $this->renderedContent);
    }
}

?>