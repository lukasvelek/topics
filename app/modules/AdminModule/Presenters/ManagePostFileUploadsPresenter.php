<?php

namespace App\Modules\AdminModule;

use App\Core\AjaxRequestBuilder;
use App\Entities\PostImageFileEntity;
use App\Exceptions\AException;
use App\Helpers\DateTimeFormatHelper;
use App\UI\GridBuilder\Cell;
use App\UI\GridBuilder\GridBuilder;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;
use Exception;

class ManagePostFileUploadsPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ManagePostFileUploadsPresenter', 'Post file uploads management');

        $this->addBeforeRenderCallback(function() {
            $this->template->sidebar = $this->createManageSidebar();
        });
    }

    public function handleList() {
        $arb = new AjaxRequestBuilder();

        $arb->setAction($this, 'getGrid')
            ->setMethod()
            ->setHeader(['gridPage' => '_page'])
            ->setFunctionName('getGrid')
            ->setFunctionArguments(['_page'])
            ->updateHTMLElement('grid-content', 'grid')
        ;

        $this->addScript($arb->build());
        $this->addScript('getGrid(0)');

        $links = [];

        $this->saveToPresenterCache('links', $links);
    }

    public function renderList() {
        $links = $this->loadFromPresenterCache('links');

        $this->template->links = $links;
    }

    public function actionGetGrid() {
        global $app;

        $page = $this->httpGet('gridPage');

        $gridSize = $app->cfg['GRID_SIZE'];

        $fileUploads = $app->fileUploadRepository->getAllFilesForGrid($gridSize, ($page * $gridSize));
        $totalCount = count($app->fileUploadRepository->getAllFilesForGrid(0, 0));
        $lastPage = ceil($totalCount / $gridSize);

        $gb = new GridBuilder();
        $gb->addColumns(['post' => 'Post', 'user' => 'User', 'filepath' => 'File path', 'filename' => 'Filename', 'dateCreated' => 'Date created']);
        $gb->addDataSource($fileUploads);
        $gb->addGridPaging($page, $lastPage, $gridSize, $totalCount, 'getGrid');
        $gb->addOnColumnRender('post', function(Cell $cell, PostImageFileEntity $pife) use ($app) {
            $post = $app->postRepository->getPostById($pife->getPostId());

            $a = HTML::a();

            $a->href($this->createFullURLString('UserModule:Posts', 'profile', ['postId' => $post->getId()]))
                ->text($post->getTitle())
                ->class('grid-link');
            
            return $a->render();
        });
        $gb->addOnColumnRender('user', function(Cell $cell, PostImageFileEntity $pife) use ($app) {
            $user = $app->userRepository->getUserById($pife->getUserId());

            $a = HTML::a();

            $a->href($this->createFullURLString('UserModule:Users', 'profile', ['userId' => $user->getId()]))
                ->text($user->getUsername())
                ->class('grid-link');

            return $a->render();
        });
        $gb->addOnColumnRender('dateCreated', function(Cell $cell, PostImageFileEntity $pife) {
            return DateTimeFormatHelper::formatDateToUserFriendly($pife->getDateCreated());
        });
        $gb->addAction(function(PostImageFileEntity $pife) use ($app) {
            $filepath = $app->fileUploadManager->createPostImageSourceLink($pife);

            $a = HTML::a();

            $a->onClick('openImage(\'' . $filepath . '\')')
                ->text('Open')
                ->class('grid-link')
                ->href('#');

            return $a->render();
        });
        $gb->addAction(function(PostImageFileEntity $pife) use ($app) {
            if($app->actionAuthorizator->canDeleteFileUpload($app->currentUser->getId(), $pife)) {
                return LinkBuilder::createSimpleLink('Delete', $this->createURL('delete', ['uploadId' => $pife->getId()]), 'grid-link');
            } else {
                return '-';
            }
        });

        $this->ajaxSendResponse(['grid' => $gb->build()]);
    }

    public function handleDelete() {
        global $app;

        $uploadId = $this->httpGet('uploadId', true);

        $pife = $app->fileUploadRepository->getFileById($uploadId);

        try {
            $app->fileUploadManager->deleteUploadedFile($pife, $app->currentUser->getId());

            $this->flashMessage('File deleted.', 'success');
        } catch(AException $e) {
            $this->flashMessage('File could not be deleted. Reason: ' . $e->getMessage(), 'error');
        }

        $this->redirect($this->createURL('list'));
    }
}

?>