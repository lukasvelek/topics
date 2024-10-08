<?php

namespace App\Modules\AdminModule;

use App\Core\AjaxRequestBuilder;
use App\Entities\PostImageFileEntity;
use App\Exceptions\AException;
use App\Helpers\DateTimeFormatHelper;
use App\Helpers\GridHelper;
use App\UI\GridBuilder\Cell;
use App\UI\GridBuilder\GridBuilder;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class ManagePostFileUploadsPresenter extends AAdminPresenter {
    private GridHelper $gridHelper;
    
    public function __construct() {
        parent::__construct('ManagePostFileUploadsPresenter', 'Post file uploads management');
    }

    public function startup() {
        parent::startup();
        
        $this->gridHelper = new GridHelper($this->logger, $this->getUserId());
    }

    public function handleList() {
        $filterType = $this->httpGet('filterType') ?? 'null';
        $filterKey = $this->httpGet('filterKey') ?? 'null';

        $arb = new AjaxRequestBuilder();

        $arb->setAction($this, 'getGrid')
            ->setMethod()
            ->setHeader(['gridPage' => '_page', 'filterType' => '_filterType', 'filterKey' => '_filterKey'])
            ->setFunctionName('getGrid')
            ->setFunctionArguments(['_page', '_filterType', '_filterKey'])
            ->updateHTMLElement('grid-content', 'grid')
            ->updateHTMLElement('grid-filter-control', 'filterControl')
        ;

        $this->addScript($arb->build());
        $this->addScript('getGrid(-1, \'' . $filterType . '\', \'' . $filterKey . '\')');

        $links = [];

        $this->saveToPresenterCache('links', $links);
    }

    public function renderList() {
        $links = $this->loadFromPresenterCache('links');

        $this->template->links = $links;
    }

    public function actionGetGrid() {
        $gridPage = $this->httpGet('gridPage');
        $filterType = $this->httpGet('filterType');
        $filterKey = $this->httpGet('filterKey');

        $page = $this->gridHelper->getGridPage(GridHelper::GRID_POST_FILE_UPLOADS, $gridPage, [$filterType]);

        $gridSize = $gridSize = $this->app->getGridSize();

        $fileUploads = [];
        $totalCount = 0;

        switch($filterType) {
            case 'null':
                $fileUploads = $this->app->fileUploadRepository->getAllFilesForGrid($gridSize, ($page * $gridSize));
                $totalCount = count($this->app->fileUploadRepository->getAllFilesForGrid(0, 0));
                break;

            case 'post':
                $fileUploads = $this->app->fileUploadRepository->getFilesForPostForGrid($filterKey, $gridSize, ($page * $gridSize));
                $totalCount = count($this->app->fileUploadRepository->getFilesForPostForGrid($filterKey, 0, 0));
                break;

            case 'user':
                $fileUploads = $this->app->fileUploadRepository->getFilesForUserForGrid($filterKey, $gridSize, ($page * $gridSize));
                $totalCount = count($this->app->fileUploadRepository->getFilesForUserForGrid($filterKey, 0, 0));
                break;
        }

        $lastPage = ceil($totalCount / $gridSize);

        $gb = new GridBuilder();
        $gb->addColumns(['post' => 'Post', 'user' => 'User', 'filepath' => 'File path (hover for full path)', 'filename' => 'Filename', 'dateCreated' => 'Date created']);
        $gb->addDataSource($fileUploads);
        $gb->addGridPaging($page, $lastPage, $gridSize, $totalCount, 'getGrid', [$filterType, $filterKey]);
        $gb->addOnColumnRender('post', function(Cell $cell, PostImageFileEntity $pife) {
            $post = $this->app->postRepository->getPostById($pife->getPostId());

            $a = HTML::a();

            $a->href($this->createFullURLString('UserModule:Posts', 'profile', ['postId' => $post->getId()]))
                ->text($post->getTitle())
                ->class('grid-link');
            
            return $a->render();
        });
        $gb->addOnColumnRender('user', function(Cell $cell, PostImageFileEntity $pife) {
            $user = $this->app->userRepository->getUserById($pife->getUserId());

            $a = HTML::a();

            $a->href($this->createFullURLString('UserModule:Users', 'profile', ['userId' => $user->getId()]))
                ->text($user->getUsername())
                ->class('grid-link');

            return $a->render();
        });
        $gb->addOnColumnRender('dateCreated', function(Cell $cell, PostImageFileEntity $pife) {
            $cell->setValue(DateTimeFormatHelper::formatDateToUserFriendly($pife->getDateCreated()));
            $cell->setTitle(DateTimeFormatHelper::formatDateToUserFriendly($pife->getDateCreated(), DateTimeFormatHelper::ATOM_FORMAT));
            return $cell;
        });
        $gb->addOnColumnRender('filepath', function(Cell $cell, PostImageFileEntity $pife) {
            $cell->setTitle($pife->getFilepath());

            $parts = explode('\\', $pife->getFilepath());

            $cell->setValue($parts[(count($parts) - 1)]);

            return $cell;
        });
        $gb->addAction(function(PostImageFileEntity $pife) {
            $filepath = $this->app->fileUploadManager->createPostImageSourceLink($pife);

            $a = HTML::a();

            $a->onClick('openImage(\'' . $filepath . '\')')
                ->text('Open')
                ->class('grid-link')
                ->href('#');

            return $a->render();
        });
        $gb->addAction(function(PostImageFileEntity $pife) {
            if($this->app->actionAuthorizator->canDeleteFileUpload($this->getUserId(), $pife)) {
                return LinkBuilder::createSimpleLink('Delete', $this->createURL('delete', ['uploadId' => $pife->getId()]), 'grid-link');
            } else {
                return '-';
            }
        });

        $filterControl = '';
        if($filterType != 'null') {
            /** FILTER CATEGORIES */
            $filterCategories = [
                'all' => 'All',
                'post' => 'Post',
                'user' => 'User'
            ];
            $filterCategoriesSelect = '<select name="filter-category" id="filter-category" onchange="handleFilterCategoryChange()">';
            foreach($filterCategories as $k => $v) {
                if($k == $filterType) {
                    $filterCategoriesSelect .= '<option value="' . $k . '" selected>' . $v . '</option>';
                } else {
                    $filterCategoriesSelect .= '<option value="' . $k . '">' . $v . '</option>';
                }
            }
            $filterCategoriesSelect .= '</select>';
            /** END OF FILTER CATEGORIES */

            /** FILTER SUBCATEGORIES */
            $filterSubcategoriesSelect = '<select name="filter-subcategory" id="filter-subcategory">';

            $options = [];
            switch($filterType) {
                case 'post':
                        $postIds = $this->app->fileUploadRepository->getPostIdsWithFileUploads();
                        $posts = $this->app->postRepository->bulkGetPostsByIds($postIds);

                        foreach($posts as $post) {
                            if($post->getId() == $filterKey) {
                                $options[] = '<option value="' . $post->getId() . '" selected>' . $post->getTitle() . '</option>';
                            } else {
                                $options[] = '<option value="' . $post->getId() . '">' . $post->getTitle() . '</option>';
                            }
                        }
                    break;

                case 'user':
                        $userIds = $this->app->fileUploadRepository->getUserIdsWithFileUploads();
                        $users = $this->app->userRepository->getUsersByIdBulk($userIds);

                        foreach($users as $user) {
                            if($user->getId() == $filterKey) {
                                $options[] = '<option value="' . $user->getId() . '" selected>' . $user->getUsername() . '</option>';
                            } else {
                                $options[] = '<option value="' . $user->getId() . '">' . $user->getUsername() . '</option>';
                            }
                        }
                    break;
            }

            $filterSubcategoriesSelect .= implode('', $options);
            $filterSubcategoriesSelect .= '</select>';
            /** ENDO OF FILTER SUBCATEGORIES */

            /** FILTER SUBMIT */
            $filterSubmit = '<button type="button" id="filter-submit" onclick="handleGridFilterChange()" style="border: 1px solid black">Apply filter</button>';
            /** END OF FILTER SUBMIT */

            /** FILTER CLEAR */
            $filterClear = '<button type="button" id="filter-clear" onclick="handleGridFilterClear()" style="border: 1px solid black">Clear filter</button>';
            /** END OF FILTER CLEAR */

            $filterForm = '
                <div>
                    ' . $filterCategoriesSelect . '
                    ' . $filterSubcategoriesSelect . '
                    ' . $filterSubmit . '
                    ' . $filterClear . '
                </div>
            ';

            $filterControl = $filterForm;
        } else {
            /** FILTER CATEGORIES */
            $filterCategories = [
                'all' => 'All',
                'post' => 'Post',
                'user' => 'User'
            ];
            $filterCategoriesSelect = '<select name="filter-category" id="filter-category" onchange="handleFilterCategoryChange()">';
            foreach($filterCategories as $k => $v) {
                if($k == $filterType) {
                    $filterCategoriesSelect .= '<option value="' . $k . '" selected>' . $v . '</option>';
                } else {
                    $filterCategoriesSelect .= '<option value="' . $k . '">' . $v . '</option>';
                }
            }
            $filterCategoriesSelect .= '</select>';
            /** END OF FILTER CATEGORIES */

            /** FILTER SUBCATEGORIES */
            $filterSubcategoriesSelect = '<select name="filter-subcategory" id="filter-subcategory"></select>';
            /** END OF FILTER SUBCATEGORIES */

            /** FILTER SUBMIT */
            $filterSubmit = '<button type="button" id="filter-submit" onclick="handleGridFilterChange()" style="border: 1px solid black">Apply filter</button>';
            /** END OF FILTER SUBMIT */

            $filterForm = '
                <div>
                    ' . $filterCategoriesSelect . '
                    ' . $filterSubcategoriesSelect . '
                    ' . $filterSubmit . '
                </div>
            ';

            $filterControl = $filterForm . '<script type="text/javascript" src="js/PostUploadImagesFilterHandler.js"></script><script type="text/javascript">$("#filter-subcategory").hide();$("#filter-submit").hide();</script>';
        }

        return ['grid' => $gb->build(), 'filterControl' => $filterControl];
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