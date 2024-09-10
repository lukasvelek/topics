<?php

namespace App\Components\Navbar;

use App\Managers\NotificationManager;
use App\Modules\TemplateObject;
use App\UI\IRenderable;

class Navbar implements IRenderable {
    private array $links;
    private TemplateObject $template;
    private bool $hideSearchBar;
    private bool $hasCustomLinks;
    private NotificationManager $notificationManager;
    private ?string $currentUserId;

    public function __construct(NotificationManager $notificationManager, ?string $currentUserId = null) {
        $this->links = [];
        $this->template = new TemplateObject(file_get_contents(__DIR__ . '\\template.html'));
        $this->hideSearchBar = false;
        $this->hasCustomLinks = false;
        $this->notificationManager = $notificationManager;
        $this->currentUserId = $currentUserId;

        $this->getLinks();
    }

    public function hideSearchBar(bool $hide = true) {
        $this->hideSearchBar = $hide;
        $this->template = new TemplateObject(file_get_contents(__DIR__ . '\\template-no-searchbar.html'));
    }

    public function setCustomLinks(array $links) {
        $this->links = $links;
        $this->hasCustomLinks = true;
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

        if($this->hasCustomLinks !== true && $app->currentUser->isAdmin()) {
            $linksCode .= $this->createLink(NavbarLinks::ADMINISTRATION, 'administration');
        }

        $this->template->links = $linksCode;

        if($app->currentUser !== null) {
            $profileLinkArray = NavbarLinks::USER_PROFILE;
            $profileLinkArray['userId'] = $app->currentUser->getId();

            $notificationLink = $this->createNotificationsLink();

            $this->template->user_info = [$notificationLink, $this->createLink(NavbarLinks::USER_INVITES, 'invites'), $this->createLink($profileLinkArray, /*$app->currentUser->getUsername()*/ 'me'), $this->createLink(NavbarLinks::USER_LOGOUT, 'logout')];
        } else {
            $this->template->user_info = '';
        }
        
        if($this->hideSearchBar || $app->currentUser === null) {
            $this->template->search_bar = '';
        } else {
            $this->template->search_bar = $this->createSearchBar();
        }
    }

    private function createNotificationsLink() {
        $text = 'notifications';

        if($this->currentUserId !== null) {
            $notifications = $this->notificationManager->getUnseenNotificationsForUser($this->currentUserId);

            if(count($notifications) > 0) {
                $text .= ' (' . count($notifications) . ')';
            }
        }

        return $this->createLink(NavbarLinks::USER_NOTIFICATIONS, $text);
    }

    private function createSearchBar() {
        global $app;

        $query = '';

        if(isset($_GET['q'])) {
            $query = ' value="' . htmlspecialchars($_GET['q']) . '"';
        }

        $code = '
            <input type="text" name="searchQuery" id="searchQuery" placeholder="Search..."' . $query . '>
            <button type="button" onclick="doSearch(\'' . $app->currentUser->getId() . '\')" id="searchQueryButton"><div style="-webkit-transform: rotate(45deg); -moz-transform: rotate(45deg); -o-transform: rotate(45deg); transform: rotate(45deg);">&#9906;</div></button>
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