<?php

namespace App\Components\Navbar;

use App\Constants\Systems;
use App\Helpers\LinkHelper;
use App\Managers\NotificationManager;
use App\Managers\SystemStatusManager;
use App\Modules\TemplateObject;
use App\UI\IRenderable;

class Navbar implements IRenderable {
    private array $links;
    private TemplateObject $template;
    private bool $hideSearchBar;
    private bool $hasCustomLinks;
    private NotificationManager $notificationManager;
    private ?string $currentUserId;
    private bool $isCurrentUserAdmin;
    private array $hideLinks;
    private SystemStatusManager $ssm;

    public function __construct(NotificationManager $notificationManager, SystemStatusManager $ssm, ?string $currentUserId = null) {
        $this->links = [];
        $this->template = new TemplateObject(file_get_contents(__DIR__ . '\\template.html'));
        $this->hideSearchBar = false;
        $this->hasCustomLinks = false;
        $this->notificationManager = $notificationManager;
        $this->ssm = $ssm;
        $this->currentUserId = $currentUserId;
        $this->isCurrentUserAdmin = false;
        $this->hideLinks = [];

        $this->getLinks();
    }

    public function hideLink(string $title) {
        $this->hideLinks[] = $title;
    }

    public function setIsCurrentUserIsAdmin(?bool $isCurrentUserAdmin = true) {
        if($isCurrentUserAdmin === null) {
            $isCurrentUserAdmin = false;
        }
        $this->isCurrentUserAdmin = $isCurrentUserAdmin;
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

    private function processSystems() {
        if(!$this->ssm->isSystemOn(Systems::CHATS) && !$this->ssm->isUserSuperAdministrator($this->currentUserId)) {
            $this->hideLink('chats');
        }
    }

    private function beforeRender() {
        $linksCode = '';
        
        $this->processSystems();

        foreach($this->links as $title => $link) {
            if(!in_array($title, $this->hideLinks)) {
                $linksCode .= $this->createLink($link, $title);
            }
        }

        if($this->hasCustomLinks !== true && $this->isCurrentUserAdmin) {
            $linksCode .= $this->createLink(NavbarLinks::ADMINISTRATION, 'administration');
        }

        $this->template->links = $linksCode;

        if($this->currentUserId !== null) {
            $profileLinkArray = NavbarLinks::USER_PROFILE;
            $profileLinkArray['userId'] = $this->currentUserId;

            $notificationLink = $this->createNotificationsLink();

            $userInfoLinks = ['chats' => $this->createLink(NavbarLinks::USER_CHATS, 'chats'), 'notifications' => $notificationLink, 'invites' => $this->createLink(NavbarLinks::USER_INVITES, 'invites'), 'me' => $this->createLink($profileLinkArray, 'me'), 'logout' => $this->createLink(NavbarLinks::USER_LOGOUT, 'logout')];;

            $userInfo = '';
            foreach($userInfoLinks as $title => $link) {
                if(!in_array($title, $this->hideLinks)) {
                    $userInfo .= $link;
                }
            }

            $this->template->user_info = $userInfo;
        } else {
            $this->template->user_info = '';
        }
        
        if($this->hideSearchBar || $this->currentUserId === null) {
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
        $query = '';

        if(isset($_GET['q'])) {
            $query = ' value="' . htmlspecialchars($_GET['q']) . '"';
        }

        $code = '
            <input type="text" name="searchQuery" id="searchQuery" placeholder="Search..."' . $query . '>
            <button type="button" onclick="doSearch(\'' . $this->currentUserId . '\')" id="searchQueryButton"><div style="-webkit-transform: rotate(45deg); -moz-transform: rotate(45deg); -o-transform: rotate(45deg); transform: rotate(45deg);">&#9906;</div></button>
            <script type="text/javascript" src="js/NavbarSearch.js"></script>
        ';

        return $code;
    }

    private function createLink(array $url, string $title) {
        return '<a class="navbar-link" href="' . LinkHelper::createUrlFromArray($url) . '">' . $title . '</a>';
    }

    public function render() {
        $this->beforeRender();

        return $this->template->render()->getRenderedContent();
    }
}

?>