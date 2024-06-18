<?php

use App\Entities\UserEntity;
use App\UI\GridBuilder\GridBuilder;

require_once('Ajax.php');

function getUsersGrid() {
    global $app;

    $page = httpGet('page');

    $elementsOnPage = 50;

    $userCount = $app->userRepository->getUsersCount();
    $lastPage = ceil($userCount / $elementsOnPage);
    $users = $app->userRepository->getUsersForGrid($elementsOnPage, ($page * $elementsOnPage));

    $gb = new GridBuilder();
    $gb->addColumns(['username' => 'Username', 'email' => 'Email', 'isAdmin' => 'Is administrator?']);
    $gb->addDataSource($users);
    $gb->addOnColumnRender('isAdmin', function(UserEntity $entity) {
        return $entity->isAdmin() ? 'Yes' : 'No';
    });
    $gb->addAction(function (UserEntity $user) {
        return '<a class="grid-link" href="?page=UserModule:Users&action=profile&userId=' . $user->getId() . '">Profile</a>';
    });
    $gb->addAction(function (UserEntity $user) use ($app) {
        if($user->getId() == $app->currentUser->getId()) {
            return null;
        }

        if($user->isAdmin()) {
            return '<a class="grid-link" href="?page=AdminModule:ManageUsers&action=unsetAdmin&userId=' . $user->getId() . '">Unset as administrator</a>';
        } else {
            return '<a class="grid-link" href="?page=AdminModule:ManageUsers&action=setAdmin&userId=' . $user->getId() . '">Set as administrator</a>';
        }
    });

    $paginator = $gb->createGridControls('getUsers()', $page, $lastPage, $app->currentUser->getId());

    return json_encode(['grid' => $gb->build(), 'paginator' => $paginator]);
}

?>