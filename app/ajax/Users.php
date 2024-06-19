<?php

use App\Entities\UserEntity;
use App\UI\FormBuilder\Option;
use App\UI\GridBuilder\GridBuilder;

require_once('Ajax.php');

function getUsersGrid() {
    global $app;

    $page = httpGet('page');

    $elementsOnPage = $app->cfg['GRID_SIZE'];

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
            return '-';
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

function searchUsersByUsernameForSelectForNewGroupMember() {
    global $app;

    $username = httpGet('query');
    $groupId = httpGet('groupId');
    
    $groupMembers = $app->groupRepository->getGroupMemberUserIds($groupId);

    $qb = $app->userRepository->composeStandardQuery($username, __METHOD__);
    $qb ->andWhere($qb->getColumnNotInValues('userId', $groupMembers))
        ->andWhere('isAdmin = 1')
    ;

    $users = $app->userRepository->getUsersFromQb($qb);

    $options = [];
    foreach($users as $user) {
        $option = new Option($user->getId(), $user->getUsername());

        $options[] = $option->render();
    }

    return json_encode(['users' => $options, 'count' => count($options)]);
}

?>