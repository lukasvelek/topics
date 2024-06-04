<?php

namespace App\Modules;

use App\Configuration;
use App\Core\CacheManager;
use App\Exceptions\TemplateDoesNotExistException;
use Exception;

abstract class AModule {
    protected string $title;

    protected array $presenters;

    private array $flashMessages;
    protected ?TemplateObject $template;
    private ?APresenter $presenter;

    protected function __construct(string $title) {
        $this->presenters = [];
        $this->title = $title;
        $this->flashMessages = [];
        $this->template = null;
        $this->presenter = null;
    }

    public function loadPresenters() {
        $presenters = [];

        $declaredClasses = get_declared_classes();

        foreach($declaredClasses as $declaredClass) {
            if(str_starts_with($declaredClass, 'App\\Modules\\' . $this->title) && str_ends_with($declaredClass, 'Presenter')) {
                $presenters[] = $declaredClass;
            }
        }

        $this->presenters = $presenters;
    }

    public function render(string $presenterTitle, string $actionTitle) {
        $this->beforePresenterRender($presenterTitle, $actionTitle);

        $this->renderPresenter();
        $this->renderModule();

        return $this->template->render()->getRenderedContent();
    }

    public function renderModule() {}

    public function renderPresenter() {
        $this->template = $this->presenter->render();

        return $this->fillLayout($this->flashMessages);
    }

    private function fillLayout(array $flashMessages) {
        $fmCode = '';

        if(count($flashMessages) > 0) {
            foreach($flashMessages as $fm) {
                $fmCode .= $fm . '<br>';
            }
        }
        
        $this->template->flash_messages = $fmCode;
    }

    private function getTemplate() {
        $commonLayout = __DIR__ . '\\@layout\\common.html';
        $customLayout = __DIR__ . '\\' . $this->title . '\\Presenters\\templates\\@layout\\common.html';

        $layoutContent = '';

        if(file_exists($customLayout)) {
            $layoutContent = file_get_contents($customLayout);
        } else if(!file_exists($customLayout) && file_exists($commonLayout)) {
            $layoutContent = file_get_contents($commonLayout);
        } else {
            throw new TemplateDoesNotExistException('common.html');
        }

        return new TemplateObject($layoutContent);
    }

    private function beforePresenterRender(string $presenterTitle, string $actionTitle) {
        $this->template = $this->getTemplate();

        $realPresenterTitle = 'App\\Modules\\' . $this->title . '\\' . $presenterTitle;

        $this->presenter = new $realPresenterTitle();
        $this->presenter->setTemplate($this->getTemplate());
        $this->presenter->setParams(['module' => $this->title]);
        $this->presenter->setAction($actionTitle);

        /**
         * FLASH MESSAGES
         */

        // flash messages must be last
        $flashMessages = CacheManager::loadFlashMessages();

        if($flashMessages === null) {
            return;
        }

        foreach($flashMessages as $flashMessage) {
            $type = $flashMessage['type'];
            $text = $flashMessage['text'];

            $code = '<div id="fm-' . count($this->flashMessages) . '" class="fm-' . $type . '"><p class="fm-text">' . $text . '</p></div>';

            $this->flashMessages[] = $code;
        }

        CacheManager::invalidateCache('flashMessages');
    }
}

?>