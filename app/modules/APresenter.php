<?php

namespace App\Modules;

use App\Core\CacheManager;
use App\Core\Datetypes\DateTime;
use App\Exceptions\ActionDoesNotExistException;
use App\Exceptions\NoAjaxResponseException;
use App\Exceptions\RequiredAttributeIsNotSetException;
use App\Exceptions\TemplateDoesNotExistException;
use App\UI\FormBuilder\FormResponse;

/**
 * Common presenter class that all presenters must extend. It contains useful methods and most importantly rendering functionality.
 * 
 * @author Lukas Velek
 */
abstract class APresenter extends AGUICore {
    private array $params;
    private string $name;
    private string $title;
    private ?string $action;
    private array $presenterCache;
    private array $scripts;
    private ?string $ajaxResponse;
    private bool $isStatic;

    protected ?TemplateObject $template;

    private array $beforeRenderCallbacks;
    private array $afterRenderCallbacks;

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
        $this->beforeRenderCallbacks = [];
        $this->afterRenderCallbacks = [];
        $this->action = null;
        $this->template = null;
        $this->presenterCache = [];
        $this->scripts = [];
        $this->ajaxResponse = null;
        $this->isStatic = false;
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
        $this->presenterCache[$key] = $value;
    }

    /**
     * Returns data from the "presenter cache". If no data with given key is found that it returns null.
     * 
     * @param string $key Data key
     * @return mixed Data value or null
     */
    protected function loadFromPresenterCache(string $key) {
        if(array_key_exists($key, $this->presenterCache)) {
            return $this->presenterCache[$key];
        } else {
            return null;
        }
    }

    /**
     * Saves a flash message to cache
     * 
     * @param string $text Flash message text
     * @param string $type Flash message type
     */
    protected function flashMessage(string $text, string $type = 'info') {
        CacheManager::saveFlashMessageToCache(['type' => $type, 'text' => $text]);
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
                $this->template->sys_scripts = $this->scripts;
            
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
        $this->beforeRenderCallbacks[] = $function;
    }

    /**
     * Adds a callback that is called after the presenter is rendered.
     * 
     * @param callable $function Callback
     */
    public function addAfterRenderCallback(callable $function) {
        $this->afterRenderCallbacks[] = $function;
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

        $ok = false;
        $templateContent = null;

        $handleAction = 'handle' . ucfirst($this->action);
        $renderAction = 'render' . ucfirst($this->action);
        $actionAction = 'action' . ucfirst($this->action);

        if($isAjax) {
            if(method_exists($this, $actionAction)) {
                $app->logger->stopwatch(function() use ($actionAction) {
                    return $this->$actionAction();
                }, 'App\\Modules\\' . $moduleName . '\\' . $this->title . '::' . $actionAction);

                if($this->ajaxResponse !== null) {
                    return new TemplateObject($this->ajaxResponse);
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
                throw new ActionDoesNotExistException($actionAction);
            } else {
                throw new ActionDoesNotExistException($handleAction . '\' or \'' . $renderAction);
            }
        }

        foreach($this->beforeRenderCallbacks as $callback) {
            $callback();
        }

        return $templateContent;
    }

    /**
     * Performs all after render operations. The template is rendered here. The presenter cache is erased and custom after render callbacks are called.
     */
    private function afterRender() {
        if($this->template !== null) {
            $this->template->render();
        }

        $this->presenterCache = [];

        foreach($this->afterRenderCallbacks as $callback) {
            $callback();
        }
    }

    /**
     * Adds external JS script to the page
     * 
     * @param string $scriptPath Path to the JS script
     */
    protected function addExternalScript(string $scriptPath) {
        $this->scripts[] = '<script type="text/javascript" src="' . $scriptPath . '"></script>';
    }

    /**
     * Adds JS script to the page
     * 
     * @param string $scriptContent JS script content
     */
    protected function addScript(string $scriptContent) {
        $this->scripts[] = '<script type="text/javascript">' . $scriptContent . '</script>';
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
     * Performs an ajax request
     * 
     * @param array $urlParams Parameters of the URL the ajax will call
     * @param string $method The method name
     * @param array $headParams The ajax request head parameters
     * @param string $codeWhenDone The code that is executed after the ajax request is performed.
     */
    protected function ajax(array $urlParams, string $method, array $headParams, string $codeWhenDone) {
        $this->addScript($this->composeAjaxScript($urlParams, $headParams, $method, $codeWhenDone));
    }

    protected function ajaxMethod(string $methodName, array $methodParams, array $urlParams, string $method, array $headParams, string $codeWhenDone) {
        $this->addScript($this->composeAjaxScript($urlParams, $headParams, $method, $codeWhenDone, $methodName, $methodParams));
    }

    /**
     * Creates a JS code that updates element content with given HTML ID. It implicitly performs .html() but if needed (and the page element is passed to the second parameter array) it can perform .append().
     * 
     * @param array $pageElementsValuesBinding An array with page elements and the JSON values binding
     * @param array $appendPageElements An array with page elements that must not be overwritten
     * @return string JS code
     */
    protected function ajaxUpdateElements(array $pageElementsValuesBinding, array $appendPageElements = []) {
        $tmp = [];
        foreach($pageElementsValuesBinding as $pageElement => $jsonValue) {
            $t = '$("#' . $pageElement . '").';

            if(in_array($pageElement, $appendPageElements)) {
                $t .= 'append';
            } else {
                $t .= 'html';
            }

            $t .= '(obj.' . $jsonValue . ');';

            $tmp[] = $t;
        }

        return implode(' ', $tmp);
    }

    /**
     * Sends AJAX response encoded to to JSON
     * 
     * @param array $data The response data.
     */
    protected function ajaxSendResponse(array $data) {
        $this->ajaxResponse = json_encode($data);
    }

    /**
     * Creates a AJAX script call
     * 
     * @param array $urlParams Parameters of the URL the ajax will call
     * @param array $headParams The ajax request head parameters
     * @param string $method The method name
     * @param string $codeWhenDone The code that is executed after the ajax request is performed.
     * @return string JS AJAX call code
     */
    protected function composeAjaxScript(array $urlParams, array $headParams, string $method, string $codeWhenDone, ?string $methodName = null, array $methodParams = []) {
        global $app;

        $url = $app->composeURL($urlParams);

        if(!array_key_exists('isAjax', $headParams)) {
            $headParams['isAjax'] = '1';
        }

        $params = json_encode($headParams);

        if($methodName !== null) {
            foreach($methodParams as $mp) {
                if(str_contains($params, '"$' . $mp . '"')) {
    
                    $params = str_replace('"$' . $mp . '"', $mp, $params);
                }
            }
        }

        $code = '';

        if($methodName !== null) {
            $code = 'function ' . $methodName . '(' . implode(', ', $methodParams) . ') {';
        }

        if(strtoupper($method) == 'GET') {
            $code .= '
                $.get(
                    "' . $url . '",
                    ' . $params . '
                )
                .done(function ( data ) {
                    const obj = JSON.parse(data);
                    ' . $codeWhenDone . '
                });
            ';
        }

        if($methodName !== null) {
            $code .= '}';
        }

        return $code;
    }

    /**
     * Sets the current page as static
     * 
     * @param bool $static True if the page is static and false if not
     */
    public function setStatic(bool $static = true) {
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
}

?>