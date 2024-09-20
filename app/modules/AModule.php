<?php

namespace App\Modules;

use App\Core\Caching\CacheFactory;
use App\Core\Caching\CacheNames;
use App\Exceptions\TemplateDoesNotExistException;
use App\Logger\Logger;

/**
 * The common module abstract class that every module must extend. It contains functions used for rendering the page content.
 * 
 * @author Lukas Velek
 */
abstract class AModule extends AGUICore {
    protected string $title;

    protected array $presenters;

    private array $flashMessages;
    protected ?TemplateObject $template;
    private ?APresenter $presenter;
    private ?Logger $logger;

    public array $cfg;

    /**
     * The class constructor
     * 
     * @param string $title Module title
     */
    protected function __construct(string $title) {
        $this->presenters = [];
        $this->title = $title;
        $this->flashMessages = [];
        $this->template = null;
        $this->presenter = null;
        $this->logger = null;
    }

    /**
     * Sets the logger instance
     * 
     * @param Logger $logger Logger instance
     */
    public function setLogger(Logger $logger) {
        $this->logger = $logger;
    }

    /**
     * Loads all presenters associated with the extending module
     */
    public function loadPresenters() {
        $presenters = [];

        $declaredClasses = get_declared_classes();

        foreach($declaredClasses as $declaredClass) {
            if(str_starts_with($declaredClass, 'App\\Modules\\' . $this->title) && str_ends_with($declaredClass, 'Presenter')) {
                $presenters[] = $declaredClass;
            }
        }

        $this->presenters = $presenters;
    }

    /**
     * Performs operations needed to be done before rendering, then renders the presenter and finally returns the rendered content
     * 
     * @param string $presenterTitle Presenter title
     * @param string $actionTitle Action title
     * @param bool $isAjax Is request called from AJAX?
     * @return string Rendered page content
     */
    public function render(string $presenterTitle, string $actionTitle, bool $isAjax) {
        $this->beforePresenterRender($presenterTitle, $actionTitle, $isAjax);
        
        
        $this->renderPresenter($isAjax);
        $this->renderModule();

        return $this->template->render()->getRenderedContent();
    }

    /**
     * Renders custom module page content. Currently not in use.
     */
    public function renderModule() {}

    /**
     * Renders the presenter and fetches the TemplateObject instance. It also renders flash messages.
     * 
     * @param bool $isAjax Is request called from AJAX?
     */
    public function renderPresenter(bool $isAjax) {
        $this->template = $this->presenter->render($this->title, $isAjax);

        if(!$isAjax) {
            $this->fillFlashMessages();
        }
    }

    /**
     * Fills the template with flash messages
     */
    private function fillFlashMessages() {
        $fmCode = '';

        if(count($this->flashMessages) > 0) {
            $fmCode = implode('<br>', $this->flashMessages);
        }
        
        $this->template->sys_flash_messages = $fmCode;
    }

    /**
     * Returns the default layout template. It can be the common one used for all modules or it can be a custom one.
     * 
     * @return TemplateObject Page layout TemplateObject instance
     */
    private function getTemplate() {
        $commonLayout = __DIR__ . '\\@layout\\common.html';
        $customLayout = __DIR__ . '\\' . $this->title . '\\Presenters\\templates\\@layout\\common.html';

        $layoutContent = '';

        if(file_exists($customLayout)) {
            $layoutContent = file_get_contents($customLayout);
        } else if(!file_exists($customLayout) && file_exists($commonLayout)) {
            $layoutContent = file_get_contents($commonLayout);
        } else {
            throw new TemplateDoesNotExistException('common.html');
        }

        return new TemplateObject($layoutContent);
    }

    /**
     * Performs operations that must be done before rendering the presenter. Here is the default layout template loaded, presenter instantiated and flash messages loaded from cache.
     * 
     * @param string $presenterTitle Presenter title
     * @param string $actionTitle Action title
     * @param bool $isAjax Is the request called from AJAX?
     */
    private function beforePresenterRender(string $presenterTitle, string $actionTitle, bool $isAjax) {
        $this->template = $this->getTemplate();

        $realPresenterTitle = 'App\\Modules\\' . $this->title . '\\' . $presenterTitle;

        $this->presenter = new $realPresenterTitle();
        $this->presenter->setTemplate($isAjax ? null : $this->getTemplate());
        $this->presenter->setParams(['module' => $this->title]);
        $this->presenter->setAction($actionTitle);
        $this->presenter->setLogger($this->logger);
        $this->presenter->setCfg($this->cfg);

        /**
         * FLASH MESSAGES
         */

        // flash messages must be last
        if(isset($_GET['page']) && $_GET['page'] == 'AnonymModule:Login' && isset($_GET['action']) && $_GET['action'] == 'checkLogin') {
            return;
        }

        if(isset($_GET['_fm'])) {
            $cacheFactory = new CacheFactory($this->logger->getCfg());
            $cache = $cacheFactory->getCache(CacheNames::FLASH_MESSAGES);

            $flashMessages = $cache->load($_GET['_fm'], function() { return []; });

            if(empty($flashMessages)) {
                return;
            }

            foreach($flashMessages as $flashMessage) {
                $this->flashMessages[] = $this->createFlashMessage($flashMessage['type'], $flashMessage['text'], count($this->flashMessages));
            }

            $cache->invalidate();
        }
    }

    /**
     * Returns the module name
     * 
     * @return string Module name
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Sets configuration
     * 
     * @param array $cfg Configuration
     */
    public function setCfg(array $cfg) {
        $this->cfg = $cfg;
    }
}

?>