<?php

namespace App\Modules;

use App\Core\CacheManager;
use App\Exceptions\ActionDoesNotExistException;
use App\Exceptions\RequiredAttributeIsNotSetException;
use App\Exceptions\TemplateDoesNotExistException;

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

    protected function httpGet(string $key, bool $throwException = false) {
        if(isset($_GET[$key])) {
            return htmlspecialchars($_GET[$key]);
        } else {
            if($throwException) {
                throw new RequiredAttributeIsNotSetException($key, '$_GET');
            } else {
                return null;
            }
        }
    }

    protected function httpPost(string $key) {
        if(isset($_POST[$key])) {
            return htmlspecialchars($_POST[$key]);
        } else {
            return null;
        }
    }

    protected function redirect(array $url = []) {
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

    public function render(string $moduleName) {
        global $app;

        $contentTemplate = $this->beforeRender($moduleName);
        
        if($contentTemplate !== null) {
            $this->template->join($contentTemplate);
        }
        
        $renderAction = 'render' . ucfirst($this->action);
        
        if(method_exists($this, $renderAction)) {
            $app->logger->stopwatch(function() use ($renderAction) {
                return $this->$renderAction();
            }, 'App\\Modules\\' . $moduleName . '\\' . $this->title . '::' . $renderAction);
        }
        
        if($contentTemplate !== null) {
            $this->template->sys_page_content = $contentTemplate->render()->getRenderedContent();
        } else {
            $this->template->sys_page_content = '';
        }

        $this->template->sys_page_title = $this->title;
        $this->template->sys_app_name = $app->cfg['APP_NAME'];
        $this->template->sys_copyright = ((date('Y') > 2024) ? ('2024-' . date('Y')) : (date('Y')));
        
        if($app->currentUser !== null) {
            $this->template->sys_user_id = $app->currentUser->getId();
        } else {
            $this->template->sys_user_id = '';
        }
        
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

    private function beforeRender(string $moduleName) {
        global $app;

        $ok = false;
        $templateContent = null;

        $handleAction = 'handle' . ucfirst($this->action);
        $renderAction = 'render' . ucfirst($this->action);

        if(method_exists($this, $handleAction)) {
            $ok = true;
            $app->logger->stopwatch(function() use ($handleAction) {
                return $this->$handleAction();
            }, 'App\\Modules\\' . $moduleName . '\\' . $this->title . '::' . $handleAction);
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