<?php

namespace App\Modules;

use App\Core\Application;
use App\Core\FileManager;
use App\Core\Http\HttpRequest;
use App\Exceptions\GeneralException;
use App\Exceptions\RequiredAttributeIsNotSetException;
use App\Helpers\TemplateHelper;
use App\UI\AComponent;
use App\UI\FormBuilder\FormResponse;
use App\UI\IRenderable;

abstract class AGUICore {
    protected HttpRequest $httpRequest;
    protected Application $app;
    protected ?APresenter $presenter;

    /**
     * Creates a flash message and returns its HTML code
     * 
     * @param string $type Flash message type (info, success, warning, error)
     * @param string $text Flash message text
     * @param int $flashMessageCount Number of flash messages
     * @param bool $custom True if flash message has custom handler or false if not
     * @return string HTML code
     */
    protected function createFlashMessage(string $type, string $text, int $flashMessageCount, bool $custom = false) {
        $fmc = $flashMessageCount . '-' . ($custom ? '-custom' : '');
        $removeLink = '<p class="fm-text fm-link" style="cursor: pointer" onclick="closeFlashMessage(\'fm-' . $fmc . '\')">&times;</p>';

        $jsAutoRemoveScript = '<script type="text/javascript">autoHideFlashMessage(\'fm-' . $fmc . '\')</script>';

        $code = '<div id="fm-' . $fmc . '" class="row fm-' . $type . '"><div class="col-md"><p class="fm-text">' . $text . '</p></div><div class="col-md-1" id="right">' . ($custom ? '' : $removeLink) . '</div><div id="fm-' . $fmc . '-progress-bar" style="position: absolute; left: 0; bottom: 1%; border-bottom: 2px solid black"></div>' . ($custom ? '' : $jsAutoRemoveScript) . '</div>';

        return $code;
    }

    /**
     * Sets Application instance
     * 
     * @param Application $application Application instance
     */
    public function setApplication(Application $application) {
        $this->app = $application;
    }

    /**
     * Sets the http request instance
     * 
     * @param HttpRequest $request HttpRequest instance
     */
    public function setHttpRequest(HttpRequest $httpRequest) {
        $this->httpRequest = $httpRequest;
    }

    /**
     * Returns a template or null
     * 
     * @param string $file Template file path
     * @return TemplateObject|null TemplateObject if the file is found or null
     */
    protected function getTemplate(string $file) {
        if(FileManager::fileExists($file)) {
            $content = FileManager::loadFile($file);
            $template = new TemplateObject($content);

            if(isset($this->presenter)) {
                $this->checkComponents($content, $template);
            }

            return $template;
        } else {
            return null;
        }
    }

    /**
     * Checks if components exist
     * 
     * @param string $templateContent Template content
     * @param TemplateObject $template Template
     */
    private function checkComponents(string $templateContent, TemplateObject $template) {
        $components = TemplateHelper::loadComponentsFromTemplateContent($templateContent);

        foreach($components as $componentName => $componentAction) {
            if(method_exists($this, $componentAction)) {
                if(isset($_GET['isFormSubmit']) && $_GET['isFormSubmit'] == '1') {
                    $fr = $this->createFormResponse();
                    $component = $this->$componentAction($this->httpRequest, $fr);
                } else {
                    $component = $this->$componentAction($this->httpRequest);
                }

                if($component instanceof AComponent) {
                    $component->setComponentName($componentName);
                    $component->setPresenter($this->presenter);
                    $component->setApplication($this->app);
                    $component->startup();

                    $template->setComponent($componentName, $component);
                } else {
                    throw new GeneralException('Method \'' . $this::class . '::' . $componentAction . '()\' does not return a value that implements IRenderable interface.');
                }
            } else {
                throw new GeneralException('No method \'' . $this::class . '::' . $componentAction . '()\' exists.');
            }
        }
    }

    /**
     * Sets the current Presenter instance
     * 
     * @param APresenter $presenter Current presenter instance
     */
    public function setPresenter(APresenter $presenter) {
        $this->presenter = $presenter;
    }

    /**
     * Creates a form response object
     * 
     * @return null|FormResponse FormResponse or null
     */
    protected function createFormResponse() {
        if(!empty($_POST)) {
            $values = $this->getPostParams();

            return FormResponse::createFormResponseFromPostData($values);
        } else {
            return null;
        }
    }

    /**
     * Returns all query params -> the $_GET array but without the 'page' and 'action' parameters.
     * 
     * @return array Query parameters
     */
    protected function getQueryParams() {
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
    protected function getPostParams() {
        $keys = array_keys($_POST);

        $values = [];
        foreach($keys as $key) {
            $values[$key] = $this->httpPost($key);
        }

        return $values;
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
}

?>