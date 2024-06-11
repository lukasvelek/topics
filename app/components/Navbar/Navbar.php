<?php

namespace App\Components\Navbar;

use App\Modules\TemplateObject;

class Navbar {
    private array $links;
    private TemplateObject $template;
    private bool $hideSearchBar;

    public function __construct() {
        $this->links = [];
        $this->template = new TemplateObject(file_get_contents(__DIR__ . '\\template.html'));
        $this->hideSearchBar = false;

        $this->getLinks();
    }

    public function hideSearchBar(bool $hide = true) {
        $this->hideSearchBar = $hide;
    }

    public function setCustomLinks(array $links) {
        $this->links = $links;
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

        if($app->currentUser !== null) {
            $profileLinkArray = NavbarLinks::USER_PROFILE;
            $profileLinkArray['userId'] = $app->currentUser->getId();

            $this->template->user_info = [$this->createLink($profileLinkArray, $app->currentUser->getUsername()), $this->createLink(NavbarLinks::USER_LOGOUT, 'logout')];
        } else {
            $this->template->user_info = '';
        }
        
        if($this->hideSearchBar || $app->currentUser === null) {
            $this->template->search_bar = '';
        } else {
            $this->template->search_bar = $this->createSearchBar();
        }
    }

    private function createSearchBar() {
        global $app;

        $query = '';

        if(isset($_GET['q'])) {
            $query = ' value="' . htmlspecialchars($_GET['q']) . '"';
        }

        $code = '
            <input type="text" name="searchQuery" id="searchQuery" placeholder="Search topics..."' . $query . '>
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