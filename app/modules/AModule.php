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

    protected function __construct(string $title) {
        $this->presenters = [];
        $this->title = $title;
        $this->flashMessages = [];
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
        $this->beforePresenterRender();

        return $this->renderPresenter($presenterTitle, $actionTitle);
    }

    public function renderPresenter(string $presenterTitle, string $actionTitle) {
        $realPresenterTitle = 'App\\Modules\\' . $this->title . '\\' . $presenterTitle;

        $presenter = new $realPresenterTitle();
        $presenter->setParams(['module' => $this->title]);
        $presenter->setAction($actionTitle);
        [$pageContent, $pageTitle] = $presenter->render();

        return $this->fillLayout($pageContent, $pageTitle, $this->flashMessages);
    }

    private function fillLayout(string $content, string $presenterTitle, array $flashMessages) {
        $template = $this->getTemplate();

        $template->page_title = $presenterTitle;
        $template->page_content = $content;

        $fmCode = '';

        if(count($flashMessages) > 0) {
            foreach($flashMessages as $fm) {
                $fmCode .= $fm . '<br>';
            }
        }
        
        $template->flash_messages = $fmCode;

        $template->render();
        return $template->getRenderedContent();
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

    private function beforePresenterRender() {
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