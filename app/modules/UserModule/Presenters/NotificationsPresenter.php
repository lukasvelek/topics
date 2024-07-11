<?php

namespace App\Modules\UserModule;

use App\Core\AjaxRequestBuilder;
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
            ->hideHTMLElementRaw('"#notification-" + _notificationId')
            ->hideHTMLElementRaw('"#notification-" + _notificationId + "-hr"')
            ->addCustomWhenDoneCode('
                if(obj.empty == "1") {
                    $("#notifications").html(obj.text);
                }
            ')
            ->disableLoadingAnimation();

        $this->addScript($arb->build());
    }

    public function renderList() {}

    public function actionGetNotificationsList() {
        global $app;

        $notifications = $app->notificationManager->getUnseenNotificationsForUser($app->currentUser->getId());

        $listCode = '';
        foreach($notifications as $notification) {
            $closeLink = '<a class="post-data-link" href="#" onclick="closeNotification(\'' . $notification->getId() . '\')">x</a>';

            $code = '
            <div class="row" id="notification-' . $notification->getId() . '">
                <div class="col-md" id="notification-' . $notification->getId() . '-data">
                    <p class="post-text">' . $notification->getTitle() . '</p>
                    <p class="post-data">' . $notification->getMessage() . '</p>
                </div>

                <div class="col-md-1">
                    ' . $closeLink . '
                </div>
            </div>
            <hr id="notification-' . $notification->getId() . '-hr">
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

        $app->notificationManager->setNotificationAsSeen($notificationId);

        $cnt = count($app->notificationManager->getUnseenNotificationsForUser($app->currentUser->getId()));

        $text = '';
        $empty = 0;
        if($cnt > 0) {
            $text = '';
        } else {
            $empty = 1;
            $text = 'No notifications found';
        }

        $this->ajaxSendResponse(['text' => $text, 'empty' => $empty]);
    }
}

?>