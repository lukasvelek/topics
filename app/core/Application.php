<?php

namespace App\Core;

use App\Authenticators\UserAuthenticator;
use App\Authorizators\ActionAuthorizator;
use App\Authorizators\SidebarAuthorizator;
use App\Authorizators\VisibilityAuthorizator;
use App\Entities\UserEntity;
use App\Exceptions\AException;
use App\Exceptions\ModuleDoesNotExistException;
use App\Logger\Logger;
use App\Managers\ContentManager;
use App\Managers\EntityManager;
use App\Managers\FileUploadManager;
use App\Managers\MailManager;
use App\Managers\NotificationManager;
use App\Managers\ReportManager;
use App\Managers\TopicManager;
use App\Managers\TopicMembershipManager;
use App\Managers\UserFollowingManager;
use App\Managers\UserManager;
use App\Managers\UserProsecutionManager;
use App\Managers\UserRegistrationManager;
use App\Modules\ModuleManager;
use App\Repositories\ContentRegulationRepository;
use App\Repositories\ContentRepository;
use App\Repositories\FileUploadRepository;
use App\Repositories\GridExportRepository;
use App\Repositories\GroupRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\PostCommentRepository;
use App\Repositories\PostRepository;
use App\Repositories\ReportRepository;
use App\Repositories\SuggestionRepository;
use App\Repositories\SystemServicesRepository;
use App\Repositories\SystemStatusRepository;
use App\Repositories\TopicCalendarEventRepository;
use App\Repositories\TopicContentRegulationRepository;
use App\Repositories\TopicInviteRepository;
use App\Repositories\TopicMembershipRepository;
use App\Repositories\TopicPollRepository;
use App\Repositories\TopicRepository;
use App\Repositories\TopicRulesRepository;
use App\Repositories\TransactionLogRepository;
use App\Repositories\UserFollowingRepository;
use App\Repositories\UserProsecutionRepository;
use App\Repositories\UserRegistrationRepository;
use App\Repositories\UserRepository;
use App\Rpeositories\MailRepository;
use App\UI\GridBuilder\DefaultGridReducer;

/**
 * Application class that contains all objects and useful functions.
 * It is also the starting point of all the application's behavior.
 * 
 * @author Lukas Velek
 */
class Application {
    private array $modules;
    public array $cfg;
    public ?UserEntity $currentUser;

    private ?string $currentModule;
    private ?string $currentPresenter;
    private ?string $currentAction;

    private bool $isAjaxRequest;

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
    public ContentRegulationRepository $contentRegulationRepository;
    public TopicMembershipRepository $topicMembershipRepository;
    public SystemServicesRepository $systemServicesRepository;
    public TopicPollRepository $topicPollRepository;
    public TopicInviteRepository $topicInviteRepository;
    public NotificationRepository $notificationRepository;
    public FileUploadRepository $fileUploadRepository;
    public TransactionLogRepository $transactionLogRepository;
    public UserFollowingRepository $userFollowingRepository;
    public MailRepository $mailRepository;
    public UserRegistrationRepository $userRegistrationRepository;
    public ContentRepository $contentRepository;
    public TopicRulesRepository $topicRulesRepository;
    public GridExportRepository $gridExportRepository;
    public TopicCalendarEventRepository $topicCalendarEventRepository;
    public TopicContentRegulationRepository $topicContentRegulationRepository;

    public UserProsecutionManager $userProsecutionManager;
    public ContentManager $contentManager;
    public TopicMembershipManager $topicMembershipManager;
    public ServiceManager $serviceManager;
    public TopicManager $topicManager;
    public NotificationManager $notificationManager;
    public FileUploadManager $fileUploadManager;
    public UserFollowingManager $userFollowingManager;
    public MailManager $mailManager;
    public UserRegistrationManager $userRegistrationManager;
    public UserManager $userManager;
    public EntityManager $entityManager;
    public ReportManager $reportManager;

    public SidebarAuthorizator $sidebarAuthorizator;
    public ActionAuthorizator $actionAuthorizator;
    public VisibilityAuthorizator $visibilityAuthorizator;

    /**
     * The Application constructor. It creates objects of all used classes.
     */
    public function __construct() {
        global $cfg;
        $this->cfg = $cfg;

        $this->modules = [];
        $this->currentModule = null;
        $this->currentPresenter = null;
        $this->currentAction = null;
        
        $this->currentUser = null;

        $this->moduleManager = new ModuleManager();

        $this->logger = new Logger($this->cfg);
        $this->logger->info('Logger initialized.', __METHOD__);
        try {
            $this->db = new DatabaseConnection($this->cfg);
        } catch(AException $e) {
            throw $e;
        }
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
        $this->contentRegulationRepository = new ContentRegulationRepository($this->db, $this->logger);
        $this->topicMembershipRepository = new TopicMembershipRepository($this->db, $this->logger);
        $this->systemServicesRepository = new SystemServicesRepository($this->db, $this->logger);
        $this->topicPollRepository = new TopicPollRepository($this->db, $this->logger);
        $this->topicInviteRepository = new TopicInviteRepository($this->db, $this->logger);
        $this->notificationRepository = new NotificationRepository($this->db, $this->logger);
        $this->fileUploadRepository = new FileUploadRepository($this->db, $this->logger);
        $this->transactionLogRepository = new TransactionLogRepository($this->db, $this->logger);
        $this->userFollowingRepository = new UserFollowingRepository($this->db, $this->logger);
        $this->mailRepository = new MailRepository($this->db, $this->logger);
        $this->userRegistrationRepository = new UserRegistrationRepository($this->db, $this->logger);
        $this->contentRepository = new ContentRepository($this->db, $this->logger);
        $this->topicRulesRepository = new TopicRulesRepository($this->db, $this->logger);
        $this->gridExportRepository = new GridExportRepository($this->db, $this->logger);
        $this->topicCalendarEventRepository = new TopicCalendarEventRepository($this->db, $this->logger);
        $this->topicContentRegulationRepository = new TopicContentRegulationRepository($this->db, $this->logger);

        $this->userAuth = new UserAuthenticator($this->userRepository, $this->logger, $this->userProsecutionRepository);

        $this->entityManager = new EntityManager($this->logger, $this->contentRepository);
        $this->userProsecutionManager = new UserProsecutionManager($this->userProsecutionRepository, $this->userRepository, $this->logger, $this->entityManager);
        $this->notificationManager = new NotificationManager($this->logger, $this->notificationRepository, $this->entityManager);
        $this->serviceManager = new ServiceManager($this->cfg, $this->systemServicesRepository);
        $this->userFollowingManager = new UserFollowingManager($this->logger, $this->userRepository, $this->userFollowingRepository, $this->notificationManager, $this->entityManager);
        $this->mailManager = new MailManager($this->logger, $this->mailRepository, $this->userRepository, $this->cfg, $this->entityManager);
        $this->topicMembershipManager = new TopicMembershipManager($this->topicRepository, $this->topicMembershipRepository, $this->logger, $this->topicInviteRepository, $this->notificationManager, $this->mailManager, $this->userRepository, $this->entityManager);
        $this->contentManager = new ContentManager($this->topicRepository, $this->postRepository, $this->postCommentRepository, $this->cfg['FULL_DELETE'], $this->logger, $this->topicMembershipManager, $this->topicPollRepository, $this->entityManager);
        $this->userRegistrationManager = new UserRegistrationManager($this->logger, $this->userRegistrationRepository, $this->userRepository, $this->mailManager, $this->entityManager);
        $this->userManager = new UserManager($this->logger, $this->userRepository, $this->mailManager, $this->groupRepository, $this->entityManager);
        $this->reportManager = new ReportManager($this->logger, $this->entityManager, $this->reportRepository, $this->userManager);
        
        $this->sidebarAuthorizator = new SidebarAuthorizator($this->db, $this->logger, $this->userRepository, $this->groupRepository);
        $this->visibilityAuthorizator = new VisibilityAuthorizator($this->db, $this->logger, $this->groupRepository, $this->userRepository);
        $this->actionAuthorizator = new ActionAuthorizator($this->db, $this->logger, $this->userRepository, $this->groupRepository, $this->topicMembershipManager, $this->postRepository);

        $this->topicManager = new TopicManager($this->logger, $this->topicRepository, $this->topicMembershipManager, $this->visibilityAuthorizator, $this->contentManager, $this->entityManager, $this->topicRulesRepository, $this->topicContentRegulationRepository, $this->topicCalendarEventRepository);
        $this->fileUploadManager = new FileUploadManager($this->logger, $this->fileUploadRepository, $this->cfg, $this->actionAuthorizator, $this->entityManager,);

        $this->isAjaxRequest = false;

        $this->loadModules();
        
        if(!FileManager::fileExists(__DIR__ . '\\install')) {
            $this->db->installDb();
        }
    }

    /**
     * Used for old AJAX functions. It has become deprecated when AJAX functionality was implemented into presenters.
     * 
     * @param string $currentUserId Current user's ID
     * 
     * @deprecated
     */
    public function ajaxRun(string $currentUserId) {
        $this->currentUser = $this->userRepository->getUserById($currentUserId);
    }
    
    /**
     * The point where all the operations are called from.
     * It tries to authenticate the current user and then calls a render method.
     */
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

        if(isset($_GET['isAjax']) && $_GET['isAjax'] == '1') {
            $this->isAjaxRequest = true;
        }

        echo $this->render();
    }

    /**
     * Redirects current page to other page using header('Location: ') method.
     * 
     * @param array $urlParams URL params
     */
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
    
    /**
     * Creates a single line URL from a URL params array
     * 
     * @param array $param URL params
     * @return string URL
     */
    public function composeURL(array $params) {
        $url = '?';

        $tmp = [];

        foreach($params as $key => $value) {
            $tmp[] = $key . '=' . $value;
        }

        $url .= implode('&', $tmp);

        return $url;
    }

    /**
     * Saves a flash message to persistent cache
     * 
     * @param string $text Flash message text
     * @param string $type Flash message type
     */
    public function flashMessage(string $text, string $type = 'info') {
        $cm = new CacheManager($this->logger);
        $cm->saveFlashMessageToCache(['type' => $type, 'text' => $text]);
    }
    
    /**
     * Returns the rendered page content
     * 
     * First it creates a module instance, then it creates a RenderEngine instance and call it's render function.
     * 
     * @return string Page HTML content
     */
    private function render() {
        if(!in_array($this->currentModule, $this->modules)) {
            throw new ModuleDoesNotExistException($this->currentModule);
        }

        $this->logger->info('Creating module.', __METHOD__);
        $moduleObject = $this->moduleManager->createModule($this->currentModule);
        $moduleObject->setLogger($this->logger);

        $this->logger->info('Initializing render engine.', __METHOD__);
        $re = new RenderEngine($this->logger, $moduleObject, $this->currentPresenter, $this->currentAction);
        $this->logger->info('Rendering page content.', __METHOD__);
        return $re->render($this->isAjaxRequest);
    }

    /**
     * Loads modules
     */
    private function loadModules() {
        $this->logger->info('Loading modules.', __METHOD__);
        $this->modules = $this->moduleManager->loadModules();
    }

    /**
     * Returns the current module, presenter and action from URL
     */
    private function getCurrentModulePresenterAction() {
        $page = htmlspecialchars($_GET['page']);

        $pageParts = explode(':', $page);

        $this->currentModule = $pageParts[0];
        $this->currentPresenter = $pageParts[1] . 'Presenter';

        if(isset($_GET['action'])) {
            $this->currentAction = htmlspecialchars($_GET['action']);
        } else {
            $this->currentAction = 'default';
        }

        $isAjax = '0';

        if(isset($_GET['isAjax'])) {
            $isAjax = htmlspecialchars($_GET['isAjax']);
        }

        $this->logger->info('Current URL: [module => ' . $this->currentModule . ', presenter => ' . $this->currentPresenter . ', action => ' . $this->currentAction . ', isAjax => ' . $isAjax . ']', __METHOD__);
    }

    /**
     * Returns the grid size from the config file
     * 
     * @return int Grid size
     */
    public function getGridSize() {
        $gridSize = $this->cfg['GRID_SIZE'];

        if($gridSize < 1) {
            $gridSize = 1;
        }

        return $gridSize;
    }

    /**
     * Returns true if this is the development version
     * 
     * @return bool True if this is development version or false if not
     */
    public function getIsDev() {
        return $this->cfg['IS_DEV'];
    }

    /**
     * Returns DefaultGridReducer instance
     * 
     * @return DefaultGridReducer DefaultGridReducer instance
     */
    public function getGridReducer() {
        return new DefaultGridReducer($this->userRepository, $this->topicRepository, $this->postRepository);
    }
}

?>