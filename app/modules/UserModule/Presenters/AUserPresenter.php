<?php

namespace App\Modules\UserModule;

use App\Exceptions\AException;
use App\Modules\APresenter;

abstract class AUserPresenter extends APresenter {
    protected function __construct(string $name, string $title) {
        parent::__construct($name, $title);

        $this->moduleName = 'UserModule';

        $this->checkNotification();
    }

    private function checkNotification() {
        global $app;

        if($this->httpGet('notificationId') !== null && $this->httpGet('removeNotification') == '1') {
            try {
                $app->notificationRepository->beginTransaction();

                $app->notificationManager->setNotificationAsSeen($this->httpGet('notificationId'));

                $app->notificationRepository->commit($app->currentUser->getId(), __METHOD__);
            } catch(AException $e) {
                $app->notificationRepository->rollback();
                
                $this->flashMessage('Could not set notification as seen. Reason: ' . $e->getMessage(), 'error');
            }
        }
    }
}

?>