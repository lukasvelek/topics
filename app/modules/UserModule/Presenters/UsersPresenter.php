<?php

namespace App\Modules\UserModule;

use App\Modules\APresenter;

class UsersPresenter extends APresenter {
    public function __construct() {
        parent::__construct('UsersPresenter', 'Users');
    }

    public function handleProfile() {
        global $app;

        $userId = $this->httpGet('userId');
        $user = $app->userRepository->getUserById($userId);

        $this->saveToPresenterCache('user', $user);

        $postCount = $app->postRepository->getPostCountForUserId($userId);

        $this->saveToPresenterCache('postCount', $postCount);
    }

    public function renderProfile() {
        $user = $this->loadFromPresenterCache('user');
        $postCount = $this->loadFromPresenterCache('postCount');

        $this->template->username = $user->getUsername();
        $this->template->post_count = $postCount;
        $this->template->first_login_date = $user->getDateCreated();
    }
}

?>