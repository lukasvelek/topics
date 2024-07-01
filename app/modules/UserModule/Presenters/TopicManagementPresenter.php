<?php

namespace App\Modules\UserModule;

use App\Constants\TopicMemberRole;
use App\Core\AjaxRequestBuilder;
use App\Entities\TopicMemberEntity;
use App\Exceptions\AException;
use App\Modules\APresenter;
use App\UI\FormBuilder\FormBuilder;
use App\UI\GridBuilder\GridBuilder;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class TopicManagementPresenter extends APresenter {
    public function __construct() {
        parent::__construct('TopicManagementPresenter', 'Topic management');
    }

    public function handleManageRoles() {
        global $app;

        $topicId = $this->httpGet('topicId');
        $topic = $app->topicRepository->getTopicById($topicId);

        $arb = new AjaxRequestBuilder();

        $arb->setURL(['page' => 'UserModule:TopicManagement', 'action' => 'userRolesGrid'])
            ->setMethod('GET')
            ->setHeader(['topicId' => '_topicId', 'gridPage' => '_page'])
            ->setFunctionName('getUserRolesGrid')
            ->setFunctionArguments(['_topicId', '_page'])
            ->updateHTMLElement('grid-content', 'grid')
            ->updateHTMLElement('grid-paginator', 'paginator')
        ;

        $this->addScript($arb->build());
        $this->addScript('getUserRolesGrid(' . $topicId . ', 0)');

        $this->saveToPresenterCache('title', $topic->getTitle());

        $links = [
            LinkBuilder::createSimpleLink('&larr; Back', ['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topicId], 'post-data-link')
        ];

        $this->saveToPresenterCache('links', $links);
    }

    public function renderManageRoles() {
        $title = $this->loadFromPresenterCache('title');
        $links = $this->loadFromPresenterCache('links');

        $this->template->topic_title = $title;
        $this->template->links = $links;
    }

    public function actionUserRolesGrid() {
        global $app;

        $topicId = $this->httpGet('topicId');
        $page = $this->httpGet('gridPage');
        $gridSize = $app->cfg['GRID_SIZE'];

        $members = $app->topicMembershipManager->getTopicMembers($topicId, $gridSize, ($page * $gridSize));
        $allMembersCount = count($app->topicMembershipManager->getTopicMembers($topicId, 0, 0, false));
        $lastPage = ceil($allMembersCount / $gridSize) - 1;

        $gb = new GridBuilder();

        $gb->addDataSource($members);
        $gb->addColumns(['userId' => 'User', 'role' => 'Role']);
        $gb->addOnColumnRender('userId', function(TopicMemberEntity $tme) use ($app) {
            $user = $app->userRepository->getUserById($tme->getUserId());

            return LinkBuilder::createSimpleLink($user->getUsername(), ['page' => 'UserAdmin:Users', 'action' => 'profile', 'userId' => $tme->getUserId()], 'post-data-link');
        });
        $gb->addOnColumnRender('role', function (TopicMemberEntity $tme) {
            $text = TopicMemberRole::toString($tme->getRole());

            $span = HTML::span();
            $span->setText($text);
            $span->setColor(TopicMemberRole::getColorByKey($tme->getRole()));
            
            return $span->render();
        });
        $gb->addAction(function(TopicMemberEntity $tme) use ($app) {
            $link = LinkBuilder::createSimpleLink('Change role', ['page' => 'UserModule:TopicManagement', 'action' => 'changeRoleForm', 'topicId' => $tme->getTopicId(), 'userId' => $tme->getUserId()], 'post-data-link');

            if($app->actionAuthorizator->canChangeUserTopicRole($tme->getTopicId(), $app->currentUser->getId(), $tme->getUserId())) {
                return $link;
            } else {
                return '-';
            }
        });

        $paginator = $gb->createGridControls2('getUserRolesGrid', $page, $lastPage, ['topicId' => $topicId]);

        $this->ajaxSendResponse(['grid' => $gb->build(), 'paginator' => $paginator]);
    }

    public function handleChangeRoleForm() {
        global $app;

        $topicId = $this->httpGet('topicId');
        $userId = $this->httpGet('userId');
        
        if(!$app->actionAuthorizator->canChangeUserTopicRole($topicId, $app->currentUser->getId(), $userId)) {
            $this->flashMessage('You are not authorized to change role of the selected user in this topic.', 'error');
            $this->redirect(['page' => 'UserModule:TopicManagement', 'action' => 'manageRoles', 'topicId' => $topicId]);
        }

        if($this->httpGet('isSubmit') == '1') {
            $role = $this->httpPost('role');

            $ok = false;

            $app->topicMembershipRepository->beginTransaction();

            try {
                $app->topicMembershipManager->changeRole($topicId, $userId, $app->currentUser->getId(), $role);

                $ok = true;
            } catch(AException $e) {
                $app->topicMembershipRepository->rollback();
                $this->flashMessage('Could not change role of the selected user. Reason: ' . $e->getMessage(), 'error');
            }

            if($ok) {
                $app->topicMembershipRepository->commit();
                $this->flashMessage('Role of the selected user changed.', 'success');
            }

            $this->redirect(['page' => 'UserModule:TopicManagement', 'action' => 'manageRoles', 'topicId' => $topicId]);
        } else {
            $topic = $app->topicRepository->getTopicById($topicId);

            $this->saveToPresenterCache('topic', $topic);

            $memberRole = $app->topicMembershipManager->getFollowRole($topicId, $userId);

            $roleArray = TopicMemberRole::getAll();

            $roles = [];
            foreach($roleArray as $k => $v) {
                if($k == TopicMemberRole::OWNER) continue;

                $tmp = [
                    'value' => $k,
                    'text' => $v
                ];

                if($k == $memberRole) {
                    $tmp['selected'] = 'selected';
                }

                $roles[] = $tmp;
            }

            $fb = new FormBuilder();
            
            $fb->setAction(['page' => 'UserModule:TopicManagement', 'action' => 'changeRoleForm', 'isSubmit' => 1, 'topicId' => $topicId, 'userId' => $userId])
                ->addSelect('role', 'Role:', $roles, true)
                ->addSubmit('Save')
            ;

            $this->saveToPresenterCache('form', $fb);
        }
    }

    public function renderChangeRoleForm() {
        $form = $this->loadFromPresenterCache('form');
        $topic = $this->loadFromPresenterCache('topic');
        
        $this->template->form = $form;
        $this->template->topic_title = $topic->getTitle();
    }
}

?>