<?php

namespace App\Modules\UserModule;

use App\Core\AjaxRequestBuilder;
use App\Exceptions\AException;
use App\Exceptions\AjaxRequestException;
use App\Exceptions\GeneralException;

class NotificationsPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('NotificationsPresenter', 'Notifications');
    }

    public function handleList() {
        $arb = new AjaxRequestBuilder();

        $arb->setAction($this, 'getNotificationsList')
            ->setMethod()
            ->setFunctionName('getNotificationsList')
            ->updateHTMLElement('notifications', 'notifications')
            ->addWhenDoneOperation('
                if(obj.isEmpty == 1) {
                    $("#notification-links").html("");
                }
            ')
            ->disableLoadingAnimation()
        ;

        $this->addScript($arb->build());
        $this->addScript('getNotificationsList()');

        $arb = new AjaxRequestBuilder();

        $arb->setAction($this, 'close')
            ->setMethod()
            ->setHeader(['notificationId' => '_notificationId'])
            ->setFunctionName('closeNotification')
            ->setFunctionArguments(['_notificationId'])
            ->hideHTMLElementRaw('"#notification-id-" + _notificationId')
            ->hideHTMLElementRaw('"#notification-id-" + _notificationId + "-br"')
            ->addWhenDoneOperation('
                if(obj.empty == "1") {
                    $("#notifications").html(obj.text);
                }
            ')
            ->disableLoadingAnimation();

        $this->addScript($arb->build());

        $arb = new AjaxRequestBuilder();

        $arb->setAction($this, 'closeAll')
            ->setMethod()
            ->setFunctionName('closeAllNotifications')
            ->addWhenDoneOperation('
                $("#notifications").html(obj.text);
            ')
            ->disableLoadingAnimation()
        ;

        $this->addScript($arb);

        $closeAllLink = '<button type="button" id="formSubmit" onclick="closeAllNotifications()">Close all</button>';

        $links = [
            $closeAllLink
        ];

        $this->saveToPresenterCache('links', $links);
    }

    public function renderList() {
        $links = $this->loadFromPresenterCache('links');

        $this->template->links = $links;
    }

    public function actionGetNotificationsList() {
        $isEmpty = false;

        $notifications = $this->app->notificationManager->getUnseenNotificationsForUser($this->getUserId());

        $listCode = '';
        foreach($notifications as $notification) {
            $closeLink = '<a class="post-data-link" href="#" onclick="closeNotification(\'' . $notification->getId() . '\')">x</a>';

            $code = '
            <div class="row" id="notification-id-' . $notification->getId() . '">
                <div class="col-md" id="notification-' . $notification->getId() . '-data">
                    <p class="post-text">' . $notification->getTitle() . '</p>
                    <p class="post-data">' . $notification->getMessage() . '</p>
                </div>

                <div class="col-md-1">
                    ' . $closeLink . '
                </div>
            </div>
            <br id="notification-' . $notification->getId() . '-br">
            ';

            $listCode .= $code;
        }

        if(empty($notifications)) {
            $isEmpty = true;
            $listCode = '<div style="text-align: center">No notifications found</div>';
        }

        return ['notifications' => $listCode, 'isEmpty' => $isEmpty];
    }

    public function actionClose() {
        $notificationId = $this->httpGet('notificationId', true);

        try {
            $this->app->notificationRepository->beginTransaction();
            
            $this->app->notificationManager->setNotificationAsSeen($notificationId, $this->getUserId());

            $this->app->notificationRepository->commit($this->getUserId(), __METHOD__);
        } catch(AException $e) {
            $this->app->notificationRepository->rollback();

            throw new AjaxRequestException('Could not close notification.', $e);
        }

        $cnt = count($this->app->notificationManager->getUnseenNotificationsForUser($this->getUserId()));

        $text = '';
        $empty = 0;
        if($cnt > 0) {
            $text = '';
        } else {
            $empty = 1;
            $text = '<div style="text-align: center">No notifications found</div>';
        }

        return ['text' => $text, 'empty' => $empty];
    }

    public function actionCloseAll() {
        $notifications = $this->app->notificationManager->getUnseenNotificationsForUser($this->getUserId());

        try {
            $this->app->notificationRepository->beginTransaction();

            if(!$this->app->notificationManager->bulkSetNotificationsAsSeen($notifications, $this->getUserId())) {
                throw new GeneralException('Could not set notifications as seen.');
            }

            $this->app->notificationRepository->commit($this->getUserId(), __METHOD__);
        } catch(AException $e) {
            $this->app->notificationRepository->rollback();

            throw new AjaxRequestException('Could not close notification.', $e);
        }

        return ['text' => '<div style="text-align: center">No notifications found</div>'];
    }
}

?>