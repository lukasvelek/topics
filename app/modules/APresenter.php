<?php

namespace App\Modules;

use App\Core\CacheManager;
use App\Core\CookieManager;
use App\Exceptions\ActionDoesNotExistException;
use App\Exceptions\TemplateDoesNotExistException;
use Exception;

abstract class APresenter {
    private array $params;
    private string $name;
    private string $title;
    private ?string $action;
    private array $presenterCache;

    protected ?TemplateObject $template;

    private array $beforeRenderCallbacks;
    private array $afterRenderCallbacks;

    protected function __construct(string $name, string $title) {
        $this->title = $title;
        $this->name = $name;
        $this->params = [];
        $this->beforeRenderCallbacks = [];
        $this->afterRenderCallbacks = [];
        $this->action = null;
        $this->template = null;
        $this->presenterCache = [];
    }

    protected function saveToPresenterCache(mixed $key, mixed $value) {
        $this->presenterCache[$key] = $value;
    }

    protected function loadFromPresenterCache(mixed $key) {
        if(array_key_exists($key, $this->presenterCache)) {
            return $this->presenterCache[$key];
        } else {
            return null;
        }
    }

    protected function flashMessage(string $text, string $type = 'info') {
        CacheManager::saveFlashMessageToCache(['type' => $type, 'text' => $text]);
    }

    protected function httpGet(string $key) {
        if(isset($_GET[$key])) {
            return htmlspecialchars($_GET[$key]);
        } else {
            return null;
        }
    }

    protected function httpPost(string $key) {
        if(isset($_POST[$key])) {
            return htmlspecialchars($_POST[$key]);
        } else {
            return null;
        }
    }

    protected function redirect(array $url) {
        global $app;

        $app->redirect($url);
    }

    protected function httpSessionGet(string $key) {
        if(isset($_SESSION[$key])) {
            return $_SESSION[$key];
        } else {
            return null;
        }
    }

    protected function httpSessionSet(string $key, mixed $value) {
        $_SESSION[$key] = $value;
    }

    public function setParams(array $params) {
        $this->params = $params;
    }

    public function render() {
        $contentTemplate = $this->beforeRender();
        
        $this->template->join($contentTemplate);
        
        $renderAction = 'render' . ucfirst($this->action);
        
        if(method_exists($this, $renderAction)) {
            $this->$renderAction();
        }
        
        $this->template->page_content = $contentTemplate->render()->getRenderedContent();
        $this->template->page_title = $this->title;
        
        $this->afterRender();

        return $this->template;
    }

    public function addBeforeRenderCallback(callable $function) {
        $this->beforeRenderCallbacks[] = $function;
    }

    public function addAfterRenderCallback(callable $function) {
        $this->afterRenderCallbacks[] = $function;
    }

    public function setAction(string $title) {
        $this->action = $title;
    }

    public function setTemplate(TemplateObject $template) {
        $this->template = $template;
    }

    private function beforeRender() {
        $ok = false;

        $handleAction = 'handle' . ucfirst($this->action);
        $renderAction = 'render' . ucfirst($this->action);

        if(method_exists($this, $handleAction)) {
            $ok = true;
            $this->$handleAction();
        }

        if(method_exists($this, $renderAction)) {
            $ok = true;
            $templatePath = __DIR__ . '\\' . $this->params['module'] . '\\Presenters\\templates\\' . $this->name . '\\' . $this->action . '.html';

            if(!file_exists($templatePath)) {
                throw new TemplateDoesNotExistException($this->action, $templatePath);
            }

            $templateContent = new TemplateObject(file_get_contents($templatePath));
        }

        if($ok === false) {
            throw new ActionDoesNotExistException($handleAction . '\' or \'' . $renderAction);
        }

        foreach($this->beforeRenderCallbacks as $callback) {
            $callback();
        }

        return $templateContent;
    }

    private function afterRender() {
        if($this->template !== null) {
            $this->template->render();
        }

        foreach($this->afterRenderCallbacks as $callback) {
            $callback();
        }
    }
}

?>