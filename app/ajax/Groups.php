<?php

use App\Entities\GroupEntity;
use App\Entities\GroupMembershipEntity;
use App\UI\GridBuilder\GridBuilder;
use App\UI\LinkBuilder;

require_once('Ajax.php');

function getGroups() {
    global $app;

    $page = (int)(httpGet('page'));

    $elementsOnPage = $app->cfg['GRID_SIZE'];

    $count = $app->groupRepository->getGroupCount();
    $lastPage = ceil($count / $elementsOnPage);
    $groups = $app->groupRepository->getGroupsForGrid($elementsOnPage, ($page * $elementsOnPage));

    $gb = new GridBuilder();
    $gb->addColumns(['title' => 'Title', 'description' => 'Description']);
    $gb->addDataSource($groups);
    $gb->addAction(function(GroupEntity $entity) {
        return LinkBuilder::createSimpleLink('Members', ['page' => 'AdminModule:ManageGroups', 'action' => 'listMembers', 'groupId' => $entity->getId()], 'post-data-link');
    });

    $paginator = $gb->createGridControls('getGroups', $page, $lastPage, $app->currentUser->getId());

    return json_encode(['grid' => $gb->build(), 'paginator' => $paginator]);
}

function getGroupMembers() {
    global $app;

    $page = (int)(httpGet('page'));
    $groupId = (int)(httpGet('groupId'));

    $elementsOnPage = $app->cfg['GRID_SIZE'];

    $membersCount = $app->groupRepository->getGroupMembersCount($groupId);
    $lastPage = ceil($membersCount / $elementsOnPage);
    $members = $app->groupRepository->getGroupMembersForGrid($groupId, $elementsOnPage, ($page * $elementsOnPage));
    $users = [];

    foreach($members as $member) {
        $users[$member->getUserId()] = $app->userRepository->getUserById($member->getUserId());
    }

    $gb = new GridBuilder();
    $gb->addColumns(['user' => 'User', 'dateCreated' => 'Since']);
    $gb->addDataSource($members);
    $gb->addOnColumnRender('user', function(GroupMembershipEntity $entity) use ($users) {
        $user = $users[$entity->getUserId()];
        return '<a class="post-data-link" href="?page=UserModule:Users&action=profile&userId=' . $user->getId() . '">' . $user->getUsername() . '</a>';
    });
    $gb->addAction(function(GroupMembershipEntity $entity) use ($app) {
        if($app->actionAuthorizator->canRemoveMemberFromGroup($app->currentUser->getId()) && $entity->getUserId() != $app->currentUser->getId()) {
            return LinkBuilder::createSimpleLink('Remove', ['page' => 'AdminModule:ManageGroups', 'action' => 'removeMember', 'groupId' => $entity->getGroupId(), 'userId' => $entity->getUserId()], 'post-data-link');
        } else {
            return '-';
        }
    });

    $paginator = $gb->createGridControls('getUserProsecutions', $page, $lastPage, $app->currentUser->getId());

    return json_encode(['grid' => $gb->build(), 'paginator' => $paginator]);
}

?>