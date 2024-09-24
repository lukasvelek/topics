<?php

namespace App\Modules\AdminModule;

use App\Constants\SystemStatus;
use App\Core\AjaxRequestBuilder;

class ManagePresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ManagePresenter', 'Manage');

        $this->addBeforeRenderCallback(function() {
            $this->template->sidebar = $this->createManageSidebar();
        });
    }

    public function handleDashboard() {
        $statuses = $this->app->systemStatusRepository->getAllStatuses();

        $statusCode = [];
        foreach($statuses as $status) {
            $statusText = SystemStatus::toString($status->getStatus());
            $color = SystemStatus::getColorByCode($status->getStatus());

            $description = '';

            if($status->getDescription() !== null) {
                $description = '<p style="font-size: 14px">' . $status->getDescription() . '</p>';
            }

            $statusCode[] = '
                <div class="row">
                    <div class="col-md">
                        <div class="system-status-item">
                            <span class="system-status-item-title" style="font-size: 20px; margin-right: 10px">' . $status->getName() . '</span>
                            <span style="font-size: 16px"><span style="color: ' . $color . '; font-size: 23px">&#x25cf;</span> ' . $statusText . '</span>
                            ' . $description . '
                        </div>
                    </div>
                </div>
            ';
        }

        $this->saveToPresenterCache('statusCode', implode('', $statusCode));

        $arb = new AjaxRequestBuilder();
        $arb->setMethod()
            ->setAction($this, 'loadEntityCount')
            ->setFunctionName('loadEntityCount')
            ->updateHTMLElement('widget2', 'widget')
        ;

        $this->addScript($arb);
        $this->addScript('loadEntityCount()');
    }

    public function renderDashboard() {
        $widget1 = $this->loadFromPresenterCache('statusCode');

        $this->template->widget1 = $widget1;
    }

    public function actionLoadEntityCount() {
        $userCount = $this->app->userRepository->getUsersCount();
        $postCount = $this->app->postRepository->getPostCount();
        $topicCount = $this->app->topicRepository->getTopicCount();

        $widget = '<div class="row">
                    <div class="col-md">
                        <div class="system-status-item">
                            <span class="system-status-title">Users: </span><span style="font-size: 16px">' . $userCount . '</span>
                        </div>
                        <div class="system-status-item">
                            <span class="system-status-title">Posts: </span><span style="font-size: 16px">' . $postCount . '</span>
                        </div>
                        <div class="system-status-item">
                            <span class="system-status-title">Topics: </span><span style="font-size: 16px">' . $topicCount . '</span>
                        </div>
                    </div>
                </div>';

        return ['widget' => $widget];
    }
}

?>