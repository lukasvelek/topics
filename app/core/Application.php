<?php

namespace App\Core;

use App\Authenticators\UserAuthenticator;
use App\Exceptions\ModuleDoesNotExistException;
use App\Exceptions\URLParamIsNotDefinedException;
use App\Logger\Logger;
use App\Modules\ModuleManager;
use App\Repositories\UserRepository;

class Application {
    private array $modules;
    public array $cfg;

    private ?string $currentModule;
    private ?string $currentPresenter;
    private ?string $currentAction;

    private ModuleManager $moduleManager;
    private Logger $logger;
    private DatabaseConnection $db;

    private UserAuthenticator $userAuth;

    private UserRepository $userRepository;

    public function __construct() {
        require_once('config.local.php');

        $this->cfg = $cfg;

        $this->modules = [];
        $this->currentModule = null;
        $this->currentPresenter = null;
        $this->currentAction = null;

        $this->moduleManager = new ModuleManager();

        $this->logger = new Logger($this->cfg);
        $this->logger->info('Logger initialized.', __METHOD__);
        $this->db = new DatabaseConnection($this->cfg);
        $this->logger->info('Database connection established', __METHOD__);
        
        $this->userRepository = new UserRepository($this->db, $this->logger);

        $this->userAuth = new UserAuthenticator($this->userRepository);

        $this->loadModules();
    }
    
    public function run() {
        $this->getCurrentModulePresenterAction();

        $this->userAuth->fastAuthUser();

        echo $this->render();
    }

    public function redirect(array $urlParams) {
        $url = $this->composeURL($urlParams);

        header('Location: ' . $url);
        exit;
    }

    public function composeURL(array $params) {
        $url = '?';

        $tmp = [];

        foreach($params as $key => $value) {
            $tmp[] = $key . '=' . $value;
        }

        $url .= implode('&', $tmp);

        return $url;
    }
    
    private function render() {
        if(!in_array($this->currentModule, $this->modules)) {
            throw new ModuleDoesNotExistException($this->currentModule);
        }

        $this->logger->info('Creating module.', __METHOD__);
        $moduleObject = $this->moduleManager->createModule($this->currentModule);

        $this->logger->info('Initializing render engine.', __METHOD__);
        $re = new RenderEngine($moduleObject, $this->currentPresenter, $this->currentAction);
        $this->logger->info('Rendering page content.', __METHOD__);
        return $re->render();
    }

    private function loadModules() {
        $this->logger->info('Loading modules.', __METHOD__);
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

        $this->logger->info('Current URL: [module => ' . $this->currentModule . ', presenter => ' . $this->currentPresenter . ', action => ' . $this->currentAction . ']', __METHOD__);
    }
}

?>