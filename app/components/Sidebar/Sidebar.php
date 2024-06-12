<?php

namespace App\Components\Sidebar;

use App\Modules\TemplateObject;

class Sidebar {
    private array $links;
    private TemplateObject $template;

    public function __construct() {
        $this->links = [];
        $this->template = new TemplateObject(file_get_contents(__DIR__ . '\\template.html'));
    }

    public function addLink(string $title, array $url, bool $isActive = false) {
        $this->links[] = $this->createLink($title, $url, $isActive);
    }

    public function render() {
        $linkCode = implode('<br>', $this->links);
        $this->template->links = $linkCode;

        return $this->template->render()->getRenderedContent();
    }

    private function composeURL(array $urlParts) {
        $url = '?';

        $urlCouples = [];
        foreach($urlParts as $upKey => $upValue) {
            $urlCouples[] = $upKey . '=' . $upValue;
        }

        $url .= implode('&', $urlCouples);

        return $url;
    }

    private function createLink(string $title, array $url, bool $isActive) {
        $url = $this->composeURL($url);

        if($isActive) {
            $title = '<b>' . $title . '</b>';
        }

        $code = '<a class="sidebar-link" href="' . $url . '">' . $title . '</a>';
        return $code;
    }
}

?>