<?php

namespace App\Core;

use App\Exceptions\ModuleDoesNotExistException;
use App\Exceptions\URLParamIsNotDefinedException;
use App\Logger\Logger;
use App\Modules\ModuleManager;

class Application {
    private array $modules;

    private ?string $currentModule;
    private ?string $currentPresenter;
    private ?string $currentAction;

    private ModuleManager $moduleManager;
    private Logger $logger;

    public function __construct() {
        $this->modules = [];
        $this->currentModule = null;
        $this->currentPresenter = null;
        $this->currentAction = null;

        $this->moduleManager = new ModuleManager();

        $this->logger = new Logger();
        
        $this->loadModules();
    }
    
    public function run() {
        $this->getCurrentModulePresenterAction();

        echo $this->render();
    }

    public function redirect(array $urlParams) {
        $url = $this->composeURL($urlParams);

        header('Location: ' . $url);
        exit;
    }

    private function render() {
        if(!in_array($this->currentModule, $this->modules)) {
            throw new ModuleDoesNotExistException($this->currentModule);
        }

        $moduleObject = $this->moduleManager->createModule($this->currentModule);

        $re = new RenderEngine($moduleObject, $this->currentPresenter, $this->currentAction);
        return $re->render();
    }

    private function loadModules() {
        $this->modules = $this->moduleManager->loadModules();
    }

    private function getCurrentModulePresenterAction() {
        if(isset($_GET['page'])) {
            $page = htmlspecialchars($_GET['page']);

            $pageParts = explode(':', $page);

            $this->currentModule = $pageParts[0];
            $this->currentPresenter = $pageParts[1] . 'Presenter';
        } else {
            throw new URLParamIsNotDefinedException('page');
        }

        if(isset($_GET['action'])) {
            $this->currentAction = htmlspecialchars($_GET['action']);
        } else {
            throw new URLParamIsNotDefinedException('action');
        }
    }

    private function composeURL(array $params) {
        $url = '?';

        $tmp = [];

        foreach($params as $key => $value) {
            $tmp[] = $key . '=' . $value;
        }

        $url .= implode('&', $tmp);

        return $url;
    }
}

?>