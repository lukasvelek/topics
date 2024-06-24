<?php

namespace App\Modules\AdminModule;

use App\UI\LinkBuilder;

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

        switch($filter) {
            case 'topics':
                $links[] = '<a class="post-data-link" href="?page=AdminModule:ManageDeletedContent&action=list"><b>Filter topics</b></a>&nbsp;';
                $links[] = '<a class="post-data-link" href="?page=AdminModule:ManageDeletedContent&action=list&filter=posts">Filter posts</a>&nbsp;';
                $links[] = '<a class="post-data-link" href="?page=AdminModule:ManageDeletedContent&action=list&filter=comments">Filter comments</a>';
                break;

            case 'posts':
                $links[] = '<a class="post-data-link" href="?page=AdminModule:ManageDeletedContent&action=list">Filter topics</a>&nbsp;';
                $links[] = '<a class="post-data-link" href="?page=AdminModule:ManageDeletedContent&action=list&filter=posts"><b>Filter posts</b></a>&nbsp;';
                $links[] = '<a class="post-data-link" href="?page=AdminModule:ManageDeletedContent&action=list&filter=comments">Filter comments</a>';
                break;

            case 'comments':
                $links[] = '<a class="post-data-link" href="?page=AdminModule:ManageDeletedContent&action=list">Filter topics</a>&nbsp;';
                $links[] = '<a class="post-data-link" href="?page=AdminModule:ManageDeletedContent&action=list&filter=posts">Filter posts</a>&nbsp;';
                $links[] = '<a class="post-data-link" href="?page=AdminModule:ManageDeletedContent&action=list&filter=comments"><b>Filter comments</b></a>';
                break;
        }

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