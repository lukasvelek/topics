<?php

namespace App\Modules;

use App\Core\AjaxRequestBuilder;
use App\Core\Application;
use App\Core\Caching\CacheFactory;
use App\Core\Caching\CacheNames;
use App\Core\Datatypes\ArrayList;
use App\Core\Datetypes\DateTime;
use App\Core\HashManager;
use App\Entities\UserEntity;
use App\Exceptions\ActionDoesNotExistException;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Exceptions\NoAjaxResponseException;
use App\Exceptions\TemplateDoesNotExistException;
use App\Helpers\GridHelper;
use App\Logger\Logger;
use App\UI\GridBuilder2\GridBuilder;
use Exception;

/**
 * Common presenter class that all presenters must extend. It contains useful methods and most importantly rendering functionality.
 * 
 * @author Lukas Velek
 */
abstract class APresenter extends AGUICore {
    private array $params;
    public string $name;
    private string $title;
    private ?string $action;
    private ArrayList $presenterCache;
    private ArrayList $scripts;
    private ?string $ajaxResponse;
    private ?string $defaultAction;
    public ?string $moduleName;
    private bool $isAjax;
    private bool $lock;
    private ?UserEntity $currentUser;

    protected ?TemplateObject $template;
    protected ?Logger $logger;

    private ArrayList $beforeRenderCallbacks;
    private ArrayList $afterRenderCallbacks;

    protected array $cfg;
    protected ?CacheFactory $cacheFactory;

    private array $flashMessages;
    private array $specialRedirectUrlParams;
    private bool $isComponentAjax;
    private array $permanentFlashMessages;
    
    public array $components;

    /**
     * The class constructor
     * 
     * @param string $name Presenter name (the class name)
     * @param string $title Presenter title (the friendly name)
     */
    protected function __construct(string $name, string $title) {
        $this->title = $title;
        $this->name = $name;
        $this->params = [];
        $this->action = null;
        $this->template = null;
        $this->ajaxResponse = null;
        $this->logger = null;
        $this->defaultAction = null;
        $this->moduleName = null;
        $this->isAjax = false;
        $this->lock = false;
        $this->currentUser = null;
        $this->isComponentAjax = false;

        $this->presenterCache = new ArrayList();
        $this->presenterCache->setStringKeyType();
        $this->presenterCache->setEnsureKeyType(true);

        $this->scripts = new ArrayList();
        $this->beforeRenderCallbacks = new ArrayList();
        $this->afterRenderCallbacks = new ArrayList();

        $this->cacheFactory = null;

        $this->flashMessages = [];
        $this->specialRedirectUrlParams = [];
        $this->permanentFlashMessages = [];

        $this->components = [];
    }

    /**
     * Everything in startup() method is called after an instance of Presenter has been created and before other functionality-handling methods are called.
     */
    public function startup() {
        $this->cacheFactory = new CacheFactory($this->cfg);
    }

    /**
     * Returns current user's ID or null if no user is set
     * 
     * @return string|null Current user's ID or null if no user is set
     */
    public function getUserId() {
        return $this->currentUser?->getId();
    }

    /**
     * Returns current user's UserEntity instance or null if no user is set
     * 
     * @return UserEntity|null Current user's UserEntity instance or null if no user is set
     */
    public function getUser() {
        return $this->currentUser;
    }

    /**
     * Sets variables from Application instance
     */
    private function procesApplicationSet() {
        if($this->app->currentUser !== null) {
            $this->currentUser = $this->app->currentUser;
        }
    }

    /**
     * Sets Application instance
     * 
     * @param Application $app Application instance
     */
    public function setApplication(Application $app) {
        parent::setApplication($app);

        $this->procesApplicationSet();
    }

    /**
     * Locks important variables so they are readonly
     */
    public function lock() {
        $this->lock = true;
    }

    /**
     * Unlocks important variables so they are not readonly
     */
    public function unlock() {
        $this->lock = false;
    }

    /**
     * Returns if the call comes from AJAX
     * 
     * @return bool Is AJAX?
     */
    protected function isAjax() {
        return $this->isAjax;
    }

    /**
     * Sets if the call comes from AJAX
     * 
     * @param bool $isAjax Is AJAX?
     */
    public function setIsAjax(bool $isAjax) {
        if(!$this->lock) {
            $this->isAjax = $isAjax;
        }
    }

    /**
     * Returns a URL with parameters saved in the presenter class as a string (e.g. "?page=UserModule:Users&action=profile&userId=...")
     * 
     * @param string $action Action name
     * @param array $params Custom URL params
     * @return string URL as string
     */
    public function createURLString(string $action, array $params = []) {
        $urlParts = $this->createURL($action, $params);

        $tmp = [];
        foreach($urlParts as $k => $v) {
            $tmp[] = $k . '=' . $v;
        }

        return '?' . implode('&', $tmp);
    }

    /**
     * Creates a full URL with parameters and returns it as a string
     * 
     * @param string $modulePresenter Module and presenter name
     * @param string $action Action name
     * @param array $params Custom URL params
     * @return string URL as string
     */
    public function createFullURLString(string $modulePresenter, string $action, array $params = []) {
        $urlParts = $this->createFullURL($modulePresenter, $action, $params);

        $tmp = [];
        foreach($urlParts as $k => $v) {
            $tmp[] = $k . '=' . $v;
        }

        return '?' . implode('&', $tmp);
    }

    /**
     * Creates a full URL with parameters
     * 
     * @param string $modulePresenter Module and presenter name
     * @param string $action Action name
     * @param array $params Custom URL params
     * @return array URL
     */
    public function createFullURL(string $modulePresenter, string $action, array $params = []) {
        $url = ['page' => $modulePresenter, 'action' => $action];

        return array_merge($url, $params);
    }

    /**
     * Returns a URL with parameters saved in the presenter class
     * 
     * @param string $action Action name
     * @param array $params Custom URL params
     * @return array URL
     */
    public function createURL(string $action, array $params = []) {
        $module = $this->moduleName;
        $presenter = $this->getCleanName();

        $url = ['page' => $module . ':' . $presenter, 'action' => $action];

        return array_merge($url, $params);
    }

    /**
     * Returns cleaned version of the presenter's name
     * 
     * Clean means that it does not contain the word "Presenter" at the end
     * 
     * @return string Clean name or name itself
     */
    public function getCleanName() {
        if(str_contains($this->name, 'Presenter')) {
            return substr($this->name, 0, -9);
        } else {
            return $this->name;
        }
    }

    /**
     * Sets the default action name
     * 
     * @param string $actionName Default action name
     */
    public function setDefaultAction(string $actionName) {
        $this->defaultAction = $actionName;
    }

    /**
     * Sets the logger instance to be used for CacheManager
     * 
     * @param Logger $logger Logger instance
     */
    public function setLogger(Logger $logger) {
        $this->logger = $logger;
    }

    private function processHttpRequest() {
        if(isset($this->httpRequest->query['isComponent']) && $this->httpRequest->query['isComponent'] == 1) {
            $this->isComponentAjax = true;
        }
    }

    /**
     * Creates a custom flash message but instead of saving it to cache, it returns its HTML code.
     * 
     * @param string $type Flash message type
     * @param string $text Flash message text
     * @return string HTML code
     */
    protected function createCustomFlashMessage(string $type, string $text) {
        return $this->createFlashMessage($type, $text, 0, true);
    }

    /**
     * Saves data to the "presenter cache" that is temporary. It is used when passing data from handleX() method to renderX() method.
     * 
     * @param string $key Data key
     * @param mixed $value Data value
     */
    protected function saveToPresenterCache(string $key, mixed $value) {
        $this->presenterCache->set($key, $value);
    }

    /**
     * Returns data from the "presenter cache". If no data with given key is found that it returns null.
     * 
     * @param string $key Data key
     * @return mixed Data value or null
     */
    protected function loadFromPresenterCache(string $key) {
        return $this->presenterCache->get($key);
    }

    /**
     * Saves a flash message to cache. Flash messages are automatically closed.
     * 
     * @param string $text Flash message text
     * @param string $type Flash message type
     */
    protected function flashMessage(string $text, string $type = 'info', int $autoCloseLengthInSeconds = 5) {
        if(empty($this->flashMessages)) {
            $hash = HashManager::createHash(8, false);
        } else {
            $hash = $this->flashMessages[0]['hash'];
        }

        $this->flashMessages[] = ['type' => $type, 'text' => $text, 'hash' => $hash, 'autoClose' => $autoCloseLengthInSeconds];
        
        if(!array_key_exists('_fm', $this->specialRedirectUrlParams)) {
            $this->specialRedirectUrlParams['_fm'] = $hash;
        }
    }

    /**
     * Saves a permanent flash message to presenter cache. Permanent flash messages are not automatically closed.
     * 
     * @param string $text Flash message text
     * @param string $type Flash message type
     */
    protected function permanentFlashMessage(string $text, string $type = 'info') {
        $this->permanentFlashMessages[] = $this->createFlashMessage($type, $text, count($this->permanentFlashMessages), false, true);
    }

    /**
     * Returns HTML code of all permanent flash messages
     * 
     * @return string HTML code of all permanent flash messages
     */
    public function fillPermanentFlashMessages() {
        if(!empty($this->permanentFlashMessages)) {
            return implode('<br>', $this->permanentFlashMessages);
        } else {
            return '';
        }
    }

    /**
     * Redirects the current page to other page. If no parameters are provided then it just refreshes the current page.
     * 
     * @param array $url URL params
     */
    public function redirect(array $url = []) {
        if(!empty($url)) {
            if(!array_key_exists('page', $url)) {
                $url['page'] = $this->httpGet('page');
            }

            if(!empty($this->specialRedirectUrlParams)) {
                $url = array_merge($url, $this->specialRedirectUrlParams);
            }

            $this->saveFlashMessagesToCache();
        }

        $this->app->redirect($url);
    }

    /**
     * Sets system parameters in the presenter
     * 
     * @param array $params
     */
    public function setParams(array $params) {
        $this->params = $params;
    }

    /**
     * Renders the presenter. It runs operations before the rendering itself, then renders the template and finally performs operations after the rendering.
     * 
     * Here are also the macros of the common template filled.
     * 
     * @param string $moduleName Name of the current module
     * @return string Presenter template content
     */
    public function render(string $moduleName) {
        try {
            $contentTemplate = $this->beforeRender($moduleName);
        } catch(AException|Exception $e) {
            throw $e;
        }
        
        if(!$this->isAjax) {
            if($contentTemplate !== null && $this->template !== null) {
                $this->template->join($contentTemplate);
            }
            
            $renderAction = 'render' . ucfirst($this->action);
            
            if(method_exists($this, $renderAction)) {
                $this->logger->stopwatch(function() use ($renderAction) {
                    return $this->$renderAction();
                }, 'App\\Modules\\' . $moduleName . '\\' . $this->title . '::' . $renderAction);
            }
            
            if($this->template !== null) {
                if($contentTemplate !== null) {
                    $this->template->sys_page_content = $contentTemplate->render()->getRenderedContent();
                } else {
                    $this->template->sys_page_content = '';
                }
            }
    
            $this->fillSystemAttributesToTemplate();
        } else {
            $this->template = $contentTemplate;
        }
        
        $this->afterRender();

        return $this->template;
    }

    private function fillSystemAttributesToTemplate() {
        $date = new DateTime();
        $date->format('Y');
        $date = $date->getResult();

        if($this->template !== null) {
            $this->template->sys_page_title = $this->title;
            $this->template->sys_app_name = $this->cfg['APP_NAME'];
            $this->template->sys_copyright = (($date > 2024) ? ('2024-' . $date) : ($date));
            $this->template->sys_scripts = $this->scripts->getAll();
        
            if($this->currentUser !== null) {
                $this->template->sys_user_id = $this->currentUser->getId();
            } else {
                $this->template->sys_user_id = '';
            }
        }
    }

    /**
     * Adds a callback that is called before the presenter is rendered.
     * 
     * @param callable $function Callback
     */
    public function addBeforeRenderCallback(callable $function) {
        $this->beforeRenderCallbacks->add(null, $function);
    }

    /**
     * Adds a callback that is called after the presenter is rendered.
     * 
     * @param callable $function Callback
     */
    public function addAfterRenderCallback(callable $function) {
        $this->afterRenderCallbacks->add(null, $function);
    }

    /**
     * Sets the action that the presenter will perform
     * 
     * @param string $title Action name
     */
    public function setAction(string $title) {
        $this->action = $title;
    }

    /**
     * Returns the current action that the presenter will perform
     * 
     * @return string Action name
     */
    public function getAction() {
        return $this->action;
    }

    /**
     * Sets the page content template
     * 
     * @param null|TemplateObject $template Template or null
     */
    public function setTemplate(?TemplateObject $template) {
        $this->template = $template;
    }

    /**
     * This method performs all necessary operations before the presenter content is rendered.
     * E.g. it calls the 'handleX()' operation that might not need to be rendered.
     * 
     * @param string $moduleName the module name
     * @return null|TemplateObject Template content or null
     */
    private function beforeRender(string $moduleName) {
        $ok = false;
        $templateContent = null;

        $handleAction = 'handle' . ucfirst($this->action);
        $renderAction = 'render' . ucfirst($this->action);

        if($this->isAjax && !$this->isComponentAjax) {
            $result = $this->processAction($moduleName);
            if($result !== null) {
                return $result;
            }
        }

        if(method_exists($this, $handleAction)) {
            $ok = true;
            $params = $this->getQueryParams();
            $handleResult = $this->logger->stopwatch(function() use ($handleAction, $params) {
                if(isset($params['isFormSubmit']) == '1') {
                    $fr = $this->createFormResponse();
                    return $this->$handleAction($fr);
                } else {
                    return $this->$handleAction();
                }
            }, 'App\\Modules\\' . $moduleName . '\\' . $this->title . '::' . $handleAction);
        }

        if(isset($handleResult) && $handleResult !== null) {
            return new TemplateObject($handleResult);
        }

        if(method_exists($this, $renderAction)) {
            $ok = true;
            $templatePath = __DIR__ . '\\' . $this->params['module'] . '\\Presenters\\templates\\' . $this->name . '\\' . $this->action . '.html';

            if(!file_exists($templatePath)) {
                throw new TemplateDoesNotExistException($this->action, $templatePath);
            }

            $templateContent = $this->getTemplate($templatePath);
        }

        if($ok === false) {
            if($this->isAjax && !$this->isComponentAjax) {
                if($this->cfg['IS_DEV']) {
                    throw new ActionDoesNotExistException($this->action);
                } else {
                    $this->redirect(['page' => 'ErrorModule:E404', 'reason' => 'ActionDoesNotExist']);
                }
            } else {
                if($this->defaultAction !== null) {
                    $this->redirect(['page' => $moduleName . ':' . $this->title, 'action' => $this->defaultAction]);
                }

                if($this->cfg['IS_DEV']) {
                    throw new ActionDoesNotExistException($handleAction . '\' or \'' . $renderAction);
                } else {
                    $this->redirect(['page' => 'ErrorModule:E404', 'reason' => 'ActionDoesNotExist']);
                }
            }
        }

        if(isset($this->httpRequest->query['do'])) {
            $do = $this->httpRequest->query['do'];
            $doParts = explode('-', $do);
            if(count($doParts) < 2) {
                return;
            }
            $componentName = $doParts[0];
            if($this->isAjax()) {
                $methodName = 'action' . ucfirst($doParts[1]);
            } else {
                $methodName = 'handle' . ucfirst($doParts[1]);
            }
            $methodArgs = [];
            if(count($doParts) > 2) {
                for($i = 2; $i < count($doParts); $i++) {
                    $methodArgs[] = $doParts[$i];
                }
            }

            $component = $templateContent->getComponent($componentName);

            if($component !== null) {
                if(method_exists($component, $methodName)) {
                    $result = $this->logger->stopwatch(function() use ($component, $methodName) {
                        try {
                            if(isset($this->httpRequest->query['isFormSubmit']) && $this->httpRequest->query['isFormSubmit'] == '1') {
                                $fr = $this->createFormResponse();
                                $result = $component->processMethod($methodName, [$this->httpRequest, $fr]);
                            } else {
                                $result = $component->processMethod($methodName, [$this->httpRequest]);
                            }
                        } catch(AException|Exception $e) {
                            if(!($e instanceof AException)) {
                                try {
                                    throw new GeneralException('Could not process component request. Reason: ' . $e->getMessage(), $e);
                                } catch(AException $e) {
                                    return ['error' => '1', 'errorMsg' => 'Error: ' . $e->getMessage()];
                                }
                            }
                            //return ['error' => '1', 'errorMsg' => 'Error: ' . $e->getMessage()];
                            throw $e;
                        }
                        return $result;
                    }, $componentName . '::' . $methodName);
        
                    if($this->ajaxResponse !== null) {
                        return new TemplateObject($this->ajaxResponse);
                    } else if($result !== null) {
                        return new TemplateObject(json_encode($result));
                    } else {
                        throw new NoAjaxResponseException();
                    }
                } else {
                    throw new ActionDoesNotExistException('Method \'' . $component::class . '::' . $methodName . '()\' does not exist.');
                }
            }
        }

        $this->beforeRenderCallbacks->executeCallables();

        return $templateContent;
    }

    /**
     * Performs all after render operations. The template is rendered here. The presenter cache is erased and custom after render callbacks are called.
     */
    private function afterRender() {
        if($this->template !== null) {
            $this->template->render();
        }

        $this->saveFlashMessagesToCache();

        $this->presenterCache->reset();

        $this->afterRenderCallbacks->executeCallables();
    }

    /**
     * Processes AJAX action
     * 
     * @param string $moduleName Module name
     * @return TemplateObject|null Template object or null
     */
    private function processAction(string $moduleName) {
        $actionAction = 'action' . ucfirst($this->action);

        if(method_exists($this, $actionAction)) {
            $result = $this->logger->stopwatch(function() use ($actionAction) {
                try {
                    $result = $this->$actionAction($this->httpRequest);
                } catch(AException|Exception $e) {
                    throw $e;
                    return ['error' => '1', 'errorMsg' => 'Error: ' . $e->getMessage()];
                }
                return $result;
            }, 'App\\Modules\\' . $moduleName . '\\' . $this->title . '::' . $actionAction);

            if($this->ajaxResponse !== null) {
                return new TemplateObject($this->ajaxResponse);
            } else if($result !== null) {
                return new TemplateObject(json_encode($result));
            } else {
                throw new NoAjaxResponseException();
            }
        }

        return null;
    }

    /**
     * Adds external JS script to the page
     * 
     * @param string $scriptPath Path to the JS script
     * @param bool True if type should be added or false if not
     */
    protected function addExternalScript(string $scriptPath, bool $hasType = true) {
        $this->scripts->add(null, '<script ' . ($hasType ? 'type="text/javascript" ' : '') . 'src="' . $scriptPath . '"></script>');
    }

    /**
     * Adds JS script to the page
     * 
     * @param string $scriptContent JS script content
     */
    public function addScript(AjaxRequestBuilder|string $scriptContent) {
        if($scriptContent instanceof AjaxRequestBuilder) {
            $scriptContent = $scriptContent->build();
        }
        
        $this->scripts->add(null, '<script type="text/javascript">' . $scriptContent . '</script>');
    }

    /**
     * Sets configuration
     * 
     * @param array $cfg
     */
    public function setCfg(array $cfg) {
        $this->cfg = $cfg;
    }

    /**
     * Saves flash messages to cache and then saves the cache
     */
    private function saveFlashMessagesToCache() {
        if(!empty($this->flashMessages)) {
            $cache = $this->cacheFactory->getCache(CacheNames::FLASH_MESSAGES);

            $hash = $this->flashMessages[0]['hash'];

            $cache->save($hash, function() {
                return $this->flashMessages;
            });
        }

        $this->cacheFactory->saveCaches();
    }

    /**
     * Returns a GridBuilder instance
     * 
     * @return GridBuilder GridBuilder instance
     */
    public function getGridBuilder() {
        $grid = new GridBuilder($this->httpRequest, $this->cfg);
        $helper = new GridHelper($this->logger, $this->getUserId());
        $grid->setHelper($helper);
        return $grid;
    }
}

?>