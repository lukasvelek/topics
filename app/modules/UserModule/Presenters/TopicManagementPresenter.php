<?php

namespace App\Modules\UserModule;

use App\Core\AjaxRequestBuilder;
use App\Modules\APresenter;

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
    }

    public function renderManageRoles() {
        $title = $this->loadFromPresenterCache('title');

        $this->template->topic_title = $title;
    }

    public function actionUserRolesGrid() {
        global $app;

        
    }
}

?>