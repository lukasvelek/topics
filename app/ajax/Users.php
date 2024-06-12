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
    $paginator = $gb->createGridControls('getUsers()', $page, $lastPage, $app->currentUser->getId());

    return json_encode(['grid' => $gb->build(), 'paginator' => $paginator]);
}

?>