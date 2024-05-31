<?php

namespace App\Modules;

use App\Exceptions\TemplateDoesNotExistException;
use Exception;

abstract class APresenter {
    private array $params;
    private string $name;
    private string $title;
    private ?string $action;

    protected TemplateObject $template;

    private array $beforeRenderCallbacks;
    private array $afterRenderCallbacks;

    protected function __construct(string $name, string $title) {
        $this->title = $title;
        $this->name = $name;
        $this->params = [];
        $this->beforeRenderCallbacks = [];
        $this->afterRenderCallbacks = [];
        $this->action = null;
    }

    protected function loadTemplate(string $templateName) {
        $path = __DIR__ . '\\' . $this->params['module'] . '\\Presenters\\templates\\' . $this->name . '\\' . $templateName . '.html';

        if(!file_exists($path)) {
            throw new TemplateDoesNotExistException($templateName, $path);
        }

        $templateContent = file_get_contents($path);

        return new TemplateObject($templateContent);
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

        return [$this->template->getRenderedContent(), $this->title];
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
            throw new Exception('Actions \'' . $handleAction . '\' or \'' . $renderAction . '\' are not defined!');
        }

        foreach($this->beforeRenderCallbacks as $callback) {
            $callback();
        }
    }

    private function afterRender() {
        $this->template->render();

        foreach($this->afterRenderCallbacks as $callback) {
            $callback();
        }
    }
}

?>