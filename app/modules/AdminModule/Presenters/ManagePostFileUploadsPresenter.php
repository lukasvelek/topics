<?php

namespace App\Modules\AdminModule;

use App\Core\DB\DatabaseRow;
use App\Exceptions\AException;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;

class ManagePostFileUploadsPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ManagePostFileUploadsPresenter', 'Post file uploads management');
    }

    public function startup() {
        parent::startup();

        if(!$this->app->sidebarAuthorizator->canManagePostFileUploads($this->getUserId())) {
            $this->flashMessage('You are not authorized to visit this section.');
            $this->redirect(['page' => 'AdminModule:Manage', 'action' => 'dashboard']);
        }
    }

    public function renderList() {
        $this->template->links = [];
    }

    public function createComponentGrid() {
        $grid = $this->getGridBuilder();

        $grid->createDataSourceFromQueryBuilder($this->app->fileUploadRepository->composeQueryForFiles(), 'uploadId');

        $col = $grid->addColumnText('postId', 'Post');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, HTML $html, mixed $value) {
            try {
                $post = $this->app->postManager->getPostById($this->getUserId(), $value);

                $el = HTML::el('a')
                    ->class('grid-link')
                    ->href($this->createFullURLString('UserModule:Users', 'profile', ['userId' => $value]))
                    ->text($post->getTitle());

                $html->text($el);
            } catch(AException $e) {
                $html->text('-');
            }
        };
        $grid->addColumnText('filepath', 'File path');
        $grid->addColumnText('filename', 'File name');
        $grid->addColumnDatetime('dateCreated', 'Date created');

        $openImage = $grid->addAction('openImage');
        $openImage->setTitle('Open image');
        $openImage->onCanRender[] = function() {
            return true;
        };
        $openImage->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a')
                    ->class('grid-link')
                    ->onClick('openImage(\'' . $row->filepath . '\')')
                    ->href('#');

            $html->text($el);
        };

        return $grid;
    }

    public function handleDelete() {
        $uploadId = $this->httpGet('uploadId', true);

        $pife = $this->app->fileUploadRepository->getFileById($uploadId);

        try {
            $this->app->fileUploadRepository->beginTransaction();
            
            $this->app->fileUploadManager->deleteUploadedFile($pife, $this->getUserId());

            $this->app->fileUploadRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('File deleted.', 'success');
        } catch(AException $e) {
            $this->app->fileUploadRepository->rollback();
            
            $this->flashMessage('File could not be deleted. Reason: ' . $e->getMessage(), 'error');
        }

        $this->redirect($this->createURL('list'));
    }

    public function actionGetFilterCategorySuboptions() {
        $category = $this->httpGet('category');

        $options = [];
        switch($category) {
            case 'post':
                $postIds = $this->app->fileUploadRepository->getPostIdsWithFileUploads();
                $posts = $this->app->postRepository->bulkGetPostsByIds($postIds);

                foreach($posts as $post) {
                    $options[] = '<option value="' . $post->getId() . '">' . $post->getTitle() . '</option>';
                }
            break;

        case 'user':
                $userIds = $this->app->fileUploadRepository->getUserIdsWithFileUploads();
                $users = $this->app->userRepository->getUsersByIdBulk($userIds);

                foreach($users as $user) {
                    $options[] = '<option value="' . $user->getId() . '">' . $user->getUsername() . '</option>';
                }
            break;
        }

        return ['options' => $options, 'empty' => (empty($options))];
    }
}

?>