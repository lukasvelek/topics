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
    private array $flashMessages;

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
        $this->flashMessages = [];
    }

    protected function flashMessage(string $text, string $type = 'info') {
        $code = '<div id="fm-' . count($this->flashMessages) . '" class="fm-' . $type . '"><p class="fm-text">' . $text . '</p></div>';

        //$cm = 

        $this->flashMessages[] = $code;
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
        $this->beforeRender();

        $renderAction = 'render' . ucfirst($this->action);

        if(method_exists($this, $renderAction)) {
            $this->$renderAction();
        }

        $this->afterRender();

        $content = '';

        if($this->template !== null) {
            $content = $this->template->getRenderedContent();
        }

        return [$content, $this->title, $this->flashMessages];
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

            $this->template = new TemplateObject(file_get_contents($templatePath));
        }

        if($ok === false) {
            throw new ActionDoesNotExistException($handleAction . '\' or \'' . $renderAction);
        }

        foreach($this->beforeRenderCallbacks as $callback) {
            $callback();
        }
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