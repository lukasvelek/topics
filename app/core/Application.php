<?php

namespace App\Core;

use App\Authenticators\UserAuthenticator;
use App\Authorizators\ActionAuthorizator;
use App\Authorizators\SidebarAuthorizator;
use App\Entities\UserEntity;
use App\Exceptions\ModuleDoesNotExistException;
use App\Exceptions\URLParamIsNotDefinedException;
use App\Logger\Logger;
use App\Managers\UserProsecutionManager;
use App\Modules\ModuleManager;
use App\Repositories\GroupRepository;
use App\Repositories\PostCommentRepository;
use App\Repositories\PostRepository;
use App\Repositories\ReportRepository;
use App\Repositories\SuggestionRepository;
use App\Repositories\SystemStatusRepository;
use App\Repositories\TopicRepository;
use App\Repositories\UserProsecutionRepository;
use App\Repositories\UserRepository;

class Application {
    private array $modules;
    public array $cfg;
    public ?UserEntity $currentUser;

    private ?string $currentModule;
    private ?string $currentPresenter;
    private ?string $currentAction;

    private ModuleManager $moduleManager;
    public Logger $logger;
    private DatabaseConnection $db;

    public UserAuthenticator $userAuth;

    public UserRepository $userRepository;
    public TopicRepository $topicRepository;
    public PostRepository $postRepository;
    public PostCommentRepository $postCommentRepository;
    public SystemStatusRepository $systemStatusRepository;
    public SuggestionRepository $suggestionRepository;
    public ReportRepository $reportRepository;
    public UserProsecutionRepository $userProsecutionRepository;
    public GroupRepository $groupRepository;

    public UserProsecutionManager $userProsecutionManager;

    public SidebarAuthorizator $sidebarAuthorizator;
    public ActionAuthorizator $actionAuthorizator;

    public function __construct() {
        require_once('config.local.php');

        $this->cfg = $cfg;

        $this->modules = [];
        $this->currentModule = null;
        $this->currentPresenter = null;
        $this->currentAction = null;
        
        $this->currentUser = null;

        $this->moduleManager = new ModuleManager();

        $this->logger = new Logger($this->cfg);
        $this->logger->info('Logger initialized.', __METHOD__);
        $this->db = new DatabaseConnection($this->cfg);
        $this->logger->info('Database connection established', __METHOD__);
        
        $this->userRepository = new UserRepository($this->db, $this->logger);
        $this->topicRepository = new TopicRepository($this->db, $this->logger);
        $this->postRepository = new PostRepository($this->db, $this->logger);
        $this->postCommentRepository = new PostCommentRepository($this->db, $this->logger);
        $this->systemStatusRepository = new SystemStatusRepository($this->db, $this->logger);
        $this->suggestionRepository = new SuggestionRepository($this->db, $this->logger);
        $this->reportRepository = new ReportRepository($this->db, $this->logger);
        $this->userProsecutionRepository = new UserProsecutionRepository($this->db, $this->logger);
        $this->groupRepository = new GroupRepository($this->db, $this->logger);

        $this->userAuth = new UserAuthenticator($this->userRepository, $this->logger, $this->userProsecutionRepository);

        $this->userProsecutionManager = new UserProsecutionManager($this->userProsecutionRepository, $this->userRepository);

        $this->sidebarAuthorizator = new SidebarAuthorizator($this->db, $this->logger, $this->userRepository, $this->groupRepository);
        $this->actionAuthorizator = new ActionAuthorizator($this->db, $this->logger, $this->userRepository, $this->groupRepository);

        $this->loadModules();
    }

    public function ajaxRun(int $currentUserId) {
        $this->currentUser = $this->userRepository->getUserById($currentUserId);
    }
    
    public function run() {
        $this->getCurrentModulePresenterAction();

        $message = '';
        if($this->userAuth->fastAuthUser($message)) {
            // login
            $this->currentUser = $this->userRepository->getUserById($_SESSION['userId']);
        } else {
            if((!isset($_GET['page']) || (isset($_GET['page']) && $_GET['page'] != 'UserModule:Logout')) && !isset($_SESSION['is_logging_in'])) {
                $this->redirect(['page' => 'UserModule:Logout', 'action' => 'logout']);

                if($message != '') {
                    $this->flashMessage($message);
                }
            }
        }

        echo $this->render();
    }

    public function redirect(array $urlParams) {
        $url = '';

        if(empty($urlParams)) {
            $url = '?';
        } else {
            $url = $this->composeURL($urlParams);
        }


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

    public function flashMessage(string $text, string $type = 'info') {
        CacheManager::saveFlashMessageToCache(['type' => $type, 'text' => $text]);
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