<?php

namespace App\Modules\AnonymModule;

use App\Constants\SystemStatus;

class StatusCheckPresenter extends AAnonymPresenter {
    public function __construct() {
        parent::__construct('StatusCheckPresenter', 'Status check');
    }

    public function handleList() {
        $statuses = $this->app->systemStatusRepository->getAllStatuses();

        $statusCode = [];
        foreach($statuses as $status) {
            $statusText = SystemStatus::toString($status->getStatus());
            $color = SystemStatus::getColorByCode($status->getStatus());

            $description = '';

            if($status->getDescription() !== null) {
                $description = '<p>' . $status->getDescription() . '</p>';
            }

            $statusCode[] = '
                <div class="row">
                    <div class="col-md">
                        <div class="system-status-item">
                            <p class="system-status-item-title">' . $status->getName() . '</p>
                            <span style="font-size: 18px"><span style="color: ' . $color . '; font-size: 25px">&#x25cf;</span> ' . $statusText . '</span>
                            ' . $description . '
                        </div>
                    </div>
                </div>
            ';
        }

        $this->saveToPresenterCache('statusCode', implode('', $statusCode));
    }

    public function renderList() {
        $statusCode = $this->loadFromPresenterCache('statusCode');
        $this->template->statuses = $statusCode;
    }
}

?>