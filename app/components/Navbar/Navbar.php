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
        $this->template->search_bar = $this->createSearchBar();
    }

    private function createSearchBar() {
        global $app;

        $code = '
            <input type="text" name="searchQuery" id="searchQuery" placeholder="Search topics...">
            <button type="button" onclick="doSearch(' . $app->currentUser->getId() . ')">Search</button>
            <script type="text/javascript" src="js/NavbarSearch.js"></script>
        ';

        return $code;
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