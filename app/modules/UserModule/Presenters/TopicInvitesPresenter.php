<?php

namespace App\Modules\UserModule;

use App\Core\AjaxRequestBuilder;
use App\Entities\TopicEntity;
use App\Entities\TopicInviteEntity;
use App\UI\GridBuilder\GridBuilder;

class TopicInvitesPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('TopicInvitesPresenter', 'Topic invites');
    }

    public function handleList() {
        $arb = new AjaxRequestBuilder();

        $arb->setAction($this, 'getInvitesGrid')
            ->setMethod()
            ->setHeader(['gridPage' => '_page'])
            ->setFunctionArguments(['_page'])
            ->setFunctionName('getInvitesGrid')
            ->updateHTMLElement('grid-content', 'grid')
            ->updateHTMLElement('grid-paginator', 'paginator')
        ;

        $this->addScript($arb->build());
        $this->addScript('getInvitesGrid(0)');
    }

    public function renderList() {}

    public function actionGetInvitesGrid() {
        global $app;

        $page = $this->httpGet('gridPage');

        $gridSize = $app->cfg['GRID_SIZE'];

        $validOnly = true;

        $invites = $app->topicInviteRepository->getInvitesForUserForGrid($app->currentUser->getId(), $gridSize, ($gridSize * $page), $validOnly);
        $totalInviteCount = count($app->topicInviteRepository->getInvitesForUserForGrid($app->currentUser->getId(), 0, 0, $validOnly));

        $lastPage = ceil($totalInviteCount / $gridSize) - 1;

        $topicIds = $app->topicInviteRepository->getAllTopicsInUserInvites($app->currentUser->getId(), $validOnly);
        $topics = $app->topicRepository->bulkGetTopicsByIds($topicIds, true);

        $gb = new GridBuilder();

        $gb->addDataSource($invites);
        $gb->addColumns(['topic' => 'Topic', 'dateValid' => 'Valid until']);
        $gb->addOnColumnRender('topic', function(TopicInviteEntity $tie) use ($topics) {
            if(array_key_exists($tie->getTopicId(), $topics)) {
                $topic = $topics[$tie->getTopicId()];

                return TopicEntity::createTopicProfileLink($topic);
            } else {
                return '-';
            }
        });

        $paginator = $gb->createGridControls2('getInvitesGrid', $page, $lastPage);

        $this->ajaxSendResponse(['grid' => $gb->build(), 'paginator' => $paginator]);
    }
}

?>