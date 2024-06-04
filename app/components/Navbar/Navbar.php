<?php

namespace App\Components\Navbar;

use App\Modules\TemplateObject;

class Navbar {
    private array $links;
    private TemplateObject $template;

    public function __construct() {
        $this->links = [];
        $this->template = new TemplateObject(file_get_contents(__DIR__ . '\\template.html'));

        $this->getLinks();
    }

    private function getLinks() {
        $this->links = NavbarLinks::toArray();
    }

    private function beforeRender() {
        global $app;

        $linksCode = '';

        foreach($this->links as $title => $link) {
            $linksCode .= $this->createLink($link, $title);
        }

        $this->template->links = $linksCode;

        $profileLinkArray = NavbarLinks::USER_PROFILE;
        $profileLinkArray['userId'] = $app->currentUser->getId();

        $this->template->user_info = [$this->createLink($profileLinkArray, $app->currentUser->getUsername()), $this->createLink(NavbarLinks::USER_LOGOUT, 'logout')];
    }

    private function createLink(array $url, string $title) {
        global $app;

        return '<a class="navbar-link" href="' . $app->composeURL($url) . '">' . $title . '</a>';
    }

    public function render() {
        $this->beforeRender();

        return $this->template->render()->getRenderedContent();
    }
}

?>