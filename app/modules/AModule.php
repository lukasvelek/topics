<?php

namespace App\Modules;

use App\Core\Configuration;
use Exception;

abstract class AModule {
    protected string $title;

    protected array $presenters;

    protected function __construct(string $title) {
        $this->presenters = [];
        $this->title = $title;
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

    public function renderPresenter(string $presenterTitle, string $actionTitle) {
        $realPresenterTitle = 'App\\Modules\\' . $this->title . '\\' . $presenterTitle;

        $presenter = new $realPresenterTitle();
        $presenter->setParams(['module' => $this->title]);
        $presenter->setAction($actionTitle);
        [$pageContent, $pageTitle] = $presenter->render();

        return $this->fillLayout($pageContent, $pageTitle);
    }

    private function fillLayout(string $content, string $presenterTitle) {
        $template = $this->getTemplate();

        $template->page_title = $presenterTitle . ' - ' . Configuration::getAppName();
        $template->page_content = $content;

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
            throw new Exception('No layout template exists!');
        }

        return new TemplateObject($layoutContent);
    }
}

?>