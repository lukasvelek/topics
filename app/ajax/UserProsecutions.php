<?php

use App\Constants\UserProsecutionType;
use App\Entities\UserProsecutionEntity;
use App\Entities\UserProsecutionHistoryEntryEntity;
use App\Helpers\DateTimeFormatHelper;
use App\UI\GridBuilder\GridBuilder;
use App\UI\LinkBuilder;

require_once('Ajax.php');

function getProsecutions() {
    global $app;

    $page = (int)(httpGet('page'));

    $elementsOnPage = $app->cfg['GRID_SIZE'];

    $prosecutionCount = $app->userProsecutionRepository->getActiveProsecutionsCount();
    $lastPage = ceil($prosecutionCount / $elementsOnPage);
    $prosecutions = $app->userProsecutionRepository->getActiveProsecutionsForGrid($elementsOnPage, ($page * $elementsOnPage));

    $gb = new GridBuilder();
    $gb->addColumns(['user' => 'User', 'reason' => 'Reason', 'type' => 'Type', 'dateFrom' => 'Date from', 'dateTo' => 'Date to']);
    $gb->addDataSource($prosecutions);
    $gb->addOnColumnRender('user', function(UserProsecutionEntity $userProsecution) use ($app) {
        $user = $app->userRepository->getUserById($userProsecution->getUserId());
        return '<a class="post-data-link" href="?page=UserModule:Users&action=profile&userId=' . $user->getId() . '">' . $user->getUsername() . '</a>';
    });
    $gb->addOnColumnRender('type', function(UserProsecutionEntity $userProsecution) {
        return UserProsecutionType::toString($userProsecution->getType());
    });
    $gb->addOnColumnRender('dateFrom', function(UserProsecutionEntity $userProsecution) {
        if($userProsecution->getStartDate() !== null) {
            return DateTimeFormatHelper::formatDateToUserFriendly($userProsecution->getStartDate());
        } else {
            return '-';
        }
    });
    $gb->addOnColumnRender('dateTo', function(UserProsecutionEntity $userProsecution) {
        if($userProsecution->getEndDate() !== null) {
            return DateTimeFormatHelper::formatDateToUserFriendly($userProsecution->getEndDate());
        } else {
            return '-';
        }
    });
    $gb->addAction(function(UserProsecutionEntity $userProsecution) {
        if(($userProsecution->getType() == UserProsecutionType::PERMA_BAN || $userProsecution->getType() == UserProsecutionType::BAN) && 
            (strtotime($userProsecution->getEndDate()) > time())) {
            return LinkBuilder::createSimpleLink('Remove ban', ['page' => 'AdminModule:ManageUserProsecutions', 'action' => 'removeProsecution', 'prosecutionId' => $userProsecution->getId()], 'post-data-link');
        } else {
            return '-';
        }
    });

    $paginator = $gb->createGridControls('getUserProsecutions', $page, $lastPage, $app->currentUser->getId());

    return json_encode(['grid' => $gb->build(), 'paginator' => $paginator]);
}

function getProsecutionLog() {
    global $app;

    $page = (int)(httpGet('page'));

    $elementsOnPage = $app->cfg['GRID_SIZE'];$elementsOnPage = 50;

    $historyEntriesCount = $app->userProsecutionRepository->getProsecutionHistoryEntryCount();
    $lastPage = ceil($historyEntriesCount / $elementsOnPage);
    $historyEntries = $app->userProsecutionRepository->getProsecutionHistoryEntriesForGrid($elementsOnPage, ($page * $elementsOnPage));

    $gb = new GridBuilder();
    $gb->addColumns(['user' => 'User', 'text' => 'Text', 'dateCreated' => 'Date created']);
    $gb->addDataSource($historyEntries);
    $gb->addOnColumnRender('user', function (UserProsecutionHistoryEntryEntity $entity) use ($app) {
        $user = $app->userRepository->getUserById($entity->getUserId());
        return '<a class="post-data-link" href="?page=UserModule:Users&action=profile&userId=' . $user->getId() . '">' . $user->getUsername() . '</a>';
    });
    $gb->addOnColumnRender('dateCreated', function(UserProsecutionHistoryEntryEntity $entity) {
        return DateTimeFormatHelper::formatDateToUserFriendly($entity->getDateCreated());
    });

    return json_encode(['grid' => $gb->build(), 'paginator' => $gb->createGridControls('getUserProsecutionLog', $page, $lastPage, $app->currentUser->getId())]);
}

?>