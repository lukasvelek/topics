<?php

namespace App\Core;

use App\Modules\ModuleManager;
use Exception;

class Application {
    private array $modules;

    private ?string $currentModule;
    private ?string $currentPresenter;
    private ?string $currentAction;

    private ModuleManager $moduleManager;

    public function __construct() {
        $this->modules = [];
        $this->currentModule = null;
        $this->currentPresenter = null;
        $this->currentAction = null;

        $this->moduleManager = new ModuleManager();
        
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
            throw new Exception('There is no module named \'' . $this->currentModule . '\'!');   
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
            throw new Exception('No page is defined!');
        }

        if(isset($_GET['action'])) {
            $this->currentAction = htmlspecialchars($_GET['action']);
        } else {
            throw new Exception('No action is defined!');
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