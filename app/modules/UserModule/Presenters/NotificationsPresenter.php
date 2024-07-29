<?php

namespace App\Modules\UserModule;

use App\Core\AjaxRequestBuilder;
use App\Exceptions\AException;
use App\UI\LinkBuilder;

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
            ->addCustomWhenDoneCode('
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
            ->addCustomWhenDoneCode('
                $("#notifications").html(obj.text);
            ')
            ->disableLoadingAnimation()
        ;

        $this->addScript($arb);

        //$closeAllLink = LinkBuilder::createJSOnclickLink('Close all', 'closeAllNotifications()', 'post-data-link');
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
        global $app;

        $notifications = $app->notificationManager->getUnseenNotificationsForUser($app->currentUser->getId());

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
            $listCode = '<div style="text-align: center">No notifications found</div>';
        }

        $this->ajaxSendResponse(['notifications' => $listCode]);
    }

    public function actionClose() {
        global $app;

        $notificationId = $this->httpGet('notificationId', true);

        try {
            $app->notificationRepository->beginTransaction();
            
            $app->notificationManager->setNotificationAsSeen($notificationId);

            $app->notificationRepository->commit($app->currentUser->getId(), __METHOD__);
        } catch(AException $e) {
            $app->notificationRepository->rollback();

            $this->flashMessage('Could not close notification. Reason: ' . $e->getMessage(), 'error');
            $this->redirect();
        }

        $cnt = count($app->notificationManager->getUnseenNotificationsForUser($app->currentUser->getId()));

        $text = '';
        $empty = 0;
        if($cnt > 0) {
            $text = '';
        } else {
            $empty = 1;
            $text = '<div style="text-align: center">No notifications found</div>';
        }

        $this->ajaxSendResponse(['text' => $text, 'empty' => $empty]);
    }

    public function actionCloseAll() {
        global $app;

        $notifications = $app->notificationManager->getUnseenNotificationsForUser($app->currentUser->getId());

        try {
            $app->notificationRepository->beginTransaction();

            foreach($notifications as $notification) {
                $app->notificationManager->setNotificationAsSeen($notification->getId());
            }

            $app->notificationRepository->commit($app->currentUser->getId(), __METHOD__);
        } catch(AException $e) {
            $app->notificationRepository->rollback();
            $this->flashMessage('Could not close notifications. Reason: ' . $e->getMessage(), 'error');
        }

        $this->ajaxSendResponse(['text' => '<div style="text-align: center">No notifications found</div>']);
    }
}

?>