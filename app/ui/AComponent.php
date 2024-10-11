<?php

namespace App\UI;

use App\Core\Application;
use App\Core\Http\HttpRequest;
use App\Modules\APresenter;

/**
 * Common class for interactive components
 * 
 * @author Lukas Velek
 * @version 1.0
 */
abstract class AComponent implements IRenderable {
    protected HttpRequest $httpRequest;
    protected APresenter $presenter;
    protected Application $app;
    protected array $cfg;
    protected string $componentName;

    /**
     * Abstract class constructor
     * 
     * @param HttpRequest $httpRequest HttpRequest instance
     * @param array $cfg Application configuration
     */
    protected function __construct(HttpRequest $httpRequest, array $cfg) {
        $this->httpRequest = $httpRequest;
        $this->cfg = $cfg;
    }

    /**
     * Sets the Application instance
     * 
     * @param Application $app Application instance
     */
    public function setApplication(Application $app) {
        $this->app = $app;
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
     * Sets the component name
     * 
     * @param string $componentName Component name
     */
    public function setComponentName(string $componentName) {
        $this->componentName = $componentName;
    }

    /**
     * Initial compoonent configuration
     */
    public function startup() {}
}

?>