<?php

namespace App\Entities;

class CachedPageEntity {
    private string $pageName;
    private string $pageContent;

    public function __construct(string $name, string $content) {
        $this->pageName = $name;
        $this->pageContent = $content;
    }

    public function getName() {
        return $this->pageName;
    }

    public function getPageContent() {
        return $this->pageContent;
    }
}

?>