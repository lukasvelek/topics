<?php

namespace App\Modules;

use App\Core\CacheManager;
use App\Core\Datetypes\DateTime;
use App\Exceptions\ActionDoesNotExistException;
use App\Exceptions\RequiredAttributeIsNotSetException;
use App\Exceptions\TemplateDoesNotExistException;
use App\UI\FormBuilder\FormResponse;

abstract class APresenter extends AGUICore {
    private array $params;
    private string $name;
    private string $title;
    private ?string $action;
    private array $presenterCache;
    private array $scripts;
    private ?string $ajaxResponse;

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
        $this->scripts = [];
        $this->ajaxResponse = null;
    }

    protected function createCustomFlashMessage(string $type, string $text) {
        return $this->createFlashMessage($type, $text, 0, true);
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

        if(!empty($url)) {
            if(!array_key_exists('page', $url)) {
                $url['page'] = $this->httpGet('page');
            }
        }

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

    public function setTemplate(?TemplateObject $template) {
        $this->template = $template;
    }

    private function beforeRender(string $moduleName, bool $isAjax) {
        global $app;

        $ok = false;
        $templateContent = null;

        $handleAction = 'handle' . ucfirst($this->action);
        $renderAction = 'render' . ucfirst($this->action);

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

        if($isAjax) {
            if($this->ajaxResponse !== null) {
                return new TemplateObject($this->ajaxResponse);
            } else if($handleResult !== null) {
                return new TemplateObject($handleResult);
            }
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

    protected function addExternalScript(string $scriptPath) {
        $this->scripts[] = '<script type="text/javascript" src="' . $scriptPath . '"></script>';
    }

    protected function addScript(string $scriptContent) {
        $this->scripts[] = '<script type="text/javascript">' . $scriptContent . '</script>';
    }

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

    private function getPostParams() {
        $keys = array_keys($_POST);

        $values = [];
        foreach($keys as $key) {
            $values[$key] = $this->httpPost($key);
        }

        return $values;
    }

    private function createFormResponse() {
        if(!empty($_POST)) {
            $values = $this->getPostParams();

            var_dump($values);

            return FormResponse::createFormResponseFromPostData($values);
        } else {
            return null;
        }
    }

    protected function ajax(array $urlParams, string $method, array $headParams, string $codeWhenDone) {
        global $app;

        $url = $app->composeURL($urlParams);

        if(!array_key_exists('isAjax', $headParams)) {
            $headParams['isAjax'] = '1';
        }

        $params = json_encode($headParams);

        $code = '';

        if(strtoupper($method) == 'GET') {
            $code = '
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

        $this->addScript($code);
    }

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

    protected function ajaxSendResponse(array $data) {
        $this->ajaxResponse = json_encode($data);
    }
}

?>