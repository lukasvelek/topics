<?php

namespace App\Modules;

use App\Core\CacheManager;
use App\Exceptions\TemplateDoesNotExistException;

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
    private array $cachedPages;

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
        $this->cachedPages = [];
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

        $isCacheable = $this->renderPresenter($isAjax);
        $this->renderModule();

        return [$this->template->render()->getRenderedContent(), $isCacheable];
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
        $isCacheable = false;

        [$this->template, $isCacheable] = $this->presenter->render($this->title, $isAjax);

        if(!$isAjax) {
            $this->fillFlashMessages();
        }

        return $isCacheable;
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

        /**
         * FLASH MESSAGES
         */

        // flash messages must be last
        if(isset($_GET['page']) && $_GET['page'] == 'AnonymModule:Login' && isset($_GET['action']) && $_GET['action'] == 'checkLogin') {
            return;
        }

        $flashMessages = CacheManager::loadFlashMessages();

        if($flashMessages === null) {
            return;
        }

        foreach($flashMessages as $flashMessage) {
            $this->flashMessages[] = $this->createFlashMessage($flashMessage['type'], $flashMessage['text'], count($this->flashMessages));
        }

        CacheManager::deleteFlashMessages();
    }

    /**
     * Sets cached pages. Keys are the presenter names and the values is the page content.
     * 
     * @param array $cachedPages Array of cached pages
     */
    public function setCachedPages(array $cachedPages) {
        $this->cachedPages = $cachedPages;
    }

    /**
     * Returns the module name
     * 
     * @return string Module name
     */
    public function getTitle() {
        return $this->title;
    }
}

?>