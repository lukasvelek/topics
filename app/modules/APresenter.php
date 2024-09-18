<?php

namespace App\Modules;

use App\Core\AjaxRequestBuilder;
use App\Core\Caching\CacheFactory;
use App\Core\Caching\CacheNames;
use App\Core\Datatypes\ArrayList;
use App\Core\Datetypes\DateTime;
use App\Core\HashManager;
use App\Exceptions\ActionDoesNotExistException;
use App\Exceptions\NoAjaxResponseException;
use App\Exceptions\RequiredAttributeIsNotSetException;
use App\Exceptions\StaticPageException;
use App\Exceptions\TemplateDoesNotExistException;
use App\Logger\Logger;
use App\UI\FormBuilder\FormResponse;

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
    private bool $isStatic;
    private ?string $defaultAction;
    public ?string $moduleName;

    protected ?TemplateObject $template;
    protected ?Logger $logger;

    private ArrayList $beforeRenderCallbacks;
    private ArrayList $afterRenderCallbacks;

    protected array $cfg;
    protected ?CacheFactory $cacheFactory;

    private array $flashMessages;
    private array $specialRedirectUrlParams;

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
        $this->isStatic = false;
        $this->logger = null;
        $this->defaultAction = null;
        $this->moduleName = null;

        $this->presenterCache = new ArrayList();
        $this->presenterCache->setStringKeyType();
        $this->presenterCache->setEnsureKeyType(true);

        $this->scripts = new ArrayList();
        $this->beforeRenderCallbacks = new ArrayList();
        $this->afterRenderCallbacks = new ArrayList();

        $this->cacheFactory = null;

        $this->flashMessages = [];
        $this->specialRedirectUrlParams = [];
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
     * Saves a flash message to cache
     * 
     * @param string $text Flash message text
     * @param string $type Flash message type
     */
    protected function flashMessage(string $text, string $type = 'info') {
        if(empty($this->flashMessages)) {
            $hash = HashManager::createHash(8, false);
        } else {
            $hash = $this->flashMessages[0]['hash'];
        }

        $this->flashMessages[] = ['type' => $type, 'text' => $text, 'hash' => $hash];
        
        if(!array_key_exists('_fm', $this->specialRedirectUrlParams)) {
            $this->specialRedirectUrlParams['_fm'] = $hash;
        }
    }

    /**
     * Returns escaped value from $_GET array. It can also throw an exception if the value is not provided.
     * 
     * @param string $key Array key
     * @param bool $throwException True if exception should be thrown or false if not
     * @return mixed Escaped value or null
     */
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

    /**
     * Returns escaped value from $_POST array. It can also throw an exception if the value is not provided.
     * 
     * @param string $key Array key
     * @param bool $throwException True if exception should be thrown or false if not
     * @return mixed Escaped value or null
     */
    protected function httpPost(string $key, bool $throwException = false) {
        if(isset($_POST[$key])) {
            return htmlspecialchars($_POST[$key]);
        } else {
            if($throwException) {
                throw new RequiredAttributeIsNotSetException($key, '$_POST');
            } else {
                return null;
            }
        }
    }

    /**
     * Redirects the current page to other page. If no parameters are provided then it just refreshes the current page.
     * 
     * @param array $url URL params
     */
    protected function redirect(array $url = []) {
        global $app;

        if(!empty($url)) {
            if(!array_key_exists('page', $url)) {
                $url['page'] = $this->httpGet('page');
            }

            if(!empty($this->specialRedirectUrlParams)) {
                $url = array_merge($url, $this->specialRedirectUrlParams);
            }

            $this->saveFlashMessagesToCache();
        }

        $app->redirect($url);
    }

    /**
     * Returns data from the $_SESSION by the key
     * 
     * @param string $key Data key
     * @return mixed Data value or null
     */
    protected function httpSessionGet(string $key) {
        if(isset($_SESSION[$key])) {
            return $_SESSION[$key];
        } else {
            return null;
        }
    }

    /**
     * Sets a value to the $_SESSION
     * 
     * @param string $key Data key
     * @param mixed $value Data value
     */
    protected function httpSessionSet(string $key, mixed $value) {
        $_SESSION[$key] = $value;
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
     * @param bool $isAjax True if this request is AJAX or false if not
     */
    public function render(string $moduleName, bool $isAjax) {
        global $app;

        $contentTemplate = $this->beforeRender($moduleName, $isAjax);
        
        if(!$isAjax) {
            if($contentTemplate !== null && $this->template !== null) {
                $this->template->join($contentTemplate);
            }
            
            $renderAction = 'render' . ucfirst($this->action);
            
            if(method_exists($this, $renderAction)) {
                $app->logger->stopwatch(function() use ($renderAction) {
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
    
            $date = new DateTime();
            $date->format('Y');
            $date = $date->getResult();
    
            if($this->template !== null) {
                $this->template->sys_page_title = $this->title;
                $this->template->sys_app_name = $app->cfg['APP_NAME'];
                $this->template->sys_copyright = (($date > 2024) ? ('2024-' . $date) : ($date));
                $this->template->sys_scripts = $this->scripts->getAll();
            
                if($app->currentUser !== null) {
                    $this->template->sys_user_id = $app->currentUser->getId();
                } else {
                    $this->template->sys_user_id = '';
                }
            }
        } else {
            $this->template = $contentTemplate;
        }
        
        $this->afterRender();

        return [$this->template, $this->isStatic];
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
     * @param bool $isAjax Is request called from AJAX?
     * @return null|TemplateObject Template content or null
     */
    private function beforeRender(string $moduleName, bool $isAjax) {
        global $app;

        $this->cacheFactory = new CacheFactory($this->cfg);

        $ok = false;
        $templateContent = null;

        $handleAction = 'handle' . ucfirst($this->action);
        $renderAction = 'render' . ucfirst($this->action);
        $actionAction = 'action' . ucfirst($this->action);

        if($isAjax) {
            if(method_exists($this, $actionAction)) {
                $result = $app->logger->stopwatch(function() use ($actionAction) {
                    return $this->$actionAction();
                }, 'App\\Modules\\' . $moduleName . '\\' . $this->title . '::' . $actionAction);

                if($this->ajaxResponse !== null) {
                    return new TemplateObject($this->ajaxResponse);
                } else if($result !== null) {
                    return new TemplateObject(json_encode($result));
                } else {
                    throw new NoAjaxResponseException();
                }
            }
        }

        if(method_exists($this, $handleAction)) {
            $ok = true;
            $params = $this->getQueryParams();
            $handleResult = $app->logger->stopwatch(function() use ($handleAction, $params) {
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

        if(method_exists($this, $renderAction) && !$isAjax) {
            $ok = true;
            $templatePath = __DIR__ . '\\' . $this->params['module'] . '\\Presenters\\templates\\' . $this->name . '\\' . $this->action . '.html';

            if(!file_exists($templatePath)) {
                throw new TemplateDoesNotExistException($this->action, $templatePath);
            }

            $templateContent = new TemplateObject(file_get_contents($templatePath));
        }

        if($ok === false) {
            if($isAjax) {
                if($app->cfg['IS_DEV']) {
                    throw new ActionDoesNotExistException($actionAction);
                } else {
                    $this->redirect(['page' => 'ErrorModule:E404', 'reason' => 'ActionDoesNotExist']);
                }
            } else {
                if($this->defaultAction !== null) {
                    $this->redirect(['page' => $moduleName . ':' . $this->title, 'action' => $this->defaultAction]);
                }

                if($app->cfg['IS_DEV']) {
                    throw new ActionDoesNotExistException($handleAction . '\' or \'' . $renderAction);
                } else {
                    $this->redirect(['page' => 'ErrorModule:E404', 'reason' => 'ActionDoesNotExist']);
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
     * Returns all query params -> the $_GET array but without the 'page' and 'action' parameters.
     * 
     * @return array Query parameters
     */
    private function getQueryParams() {
        $keys = array_keys($_GET);

        $values = [];
        foreach($keys as $key) {
            if($key == 'page' || $key == 'action') {
                continue;
            }

            $values[$key] = $this->httpGet($key);
        }

        return $values;
    }

    /**
     * Returns all post params -> the $_POST array
     * 
     * @return array POST parameters
     */
    private function getPostParams() {
        $keys = array_keys($_POST);

        $values = [];
        foreach($keys as $key) {
            $values[$key] = $this->httpPost($key);
        }

        return $values;
    }

    /**
     * Creates a form response object
     * 
     * @return null|FormResponse FormResponse or null
     */
    private function createFormResponse() {
        if(!empty($_POST)) {
            $values = $this->getPostParams();

            return FormResponse::createFormResponseFromPostData($values);
        } else {
            return null;
        }
    }

    /**
     * Sets the current page as static
     * 
     * @param bool $static True if the page is static and false if not
     */
    public function setStatic(bool $static = true) {
        $methods = get_class_methods($this);

        foreach($methods as $method) {
            if(str_contains($method, 'action')) {
                throw new StaticPageException('Presenter contains AJAX requests and thus cannot be set as static.');
            }
        }

        $this->isStatic = $static;
    }

    /**
     * Returns true if the page is static
     * 
     * @return bool True if the page is static or false if not
     */
    public function isStatic() {
        return $this->isStatic;
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
}

?>