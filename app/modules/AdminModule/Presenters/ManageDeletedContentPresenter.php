<?php

namespace App\Modules\AdminModule;

class ManageDeletedContentPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ManageDeletedContentPresenter', 'Deleted content management');

        $this->addBeforeRenderCallback(function() {
            $this->template->sidebar = $this->createManageSidebar();
        });
    }
    
    public function handleList() {
        global $app;

        $filter = $this->httpGet('filter') ?? 'topics';

        $gridScript = '<script type="text/javascript">getDeletedContent(0, ' . $app->currentUser->getId() . ', \'' . $filter . '\')</script>';

        $this->saveToPresenterCache('grid', $gridScript);

        $links = [];

        $this->saveToPresenterCache('links', $links);
    }

    public function renderList() {
        $grid = $this->loadFromPresenterCache('grid');
        $links = $this->loadFromPresenterCache('links');

        $this->template->grid_script = $grid;
        $this->template->links = $links;
    }
}

?>