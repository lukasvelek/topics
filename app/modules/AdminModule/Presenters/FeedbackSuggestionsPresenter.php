<?php

namespace App\Modules\AdminModule;

use App\Constants\SuggestionCategory;
use App\Constants\SuggestionStatus;
use App\Core\AjaxRequestBuilder;
use App\Entities\UserSuggestionEntity;
use App\Exceptions\AException;
use App\Helpers\DateTimeFormatHelper;
use App\Helpers\GridHelper;
use App\Managers\EntityManager;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use App\UI\GridBuilder\Cell;
use App\UI\GridBuilder\GridBuilder;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class FeedbackSuggestionsPresenter extends AAdminPresenter {
    private GridHelper $gridHelper;

    public function __construct() {
        parent::__construct('FeedbackSuggestionsPresenter', 'Suggestions');
    }

    public function startup() {
        parent::startup();
        
        if(!$this->app->sidebarAuthorizator->canManageSuggestions($this->getUserId())) {
            $this->flashMessage('You are not authorized to visit this section.');
            $this->redirect(['page' => 'AdminModule:Feedback', 'action' => 'dashboard']);
        }

        $this->gridHelper = new GridHelper($this->logger, $this->getUserId());
    }

    public function actionSuggestionsListGrid() {
        $gridPage = $this->httpGet('gridPage');
        $filterType = $this->httpGet('filterType');
        $filterKey = $this->httpGet('filterKey');

        $page = $this->gridHelper->getGridPage(GridHelper::GRID_SUGGESTIONS, $gridPage, [$filterType]);

        $gridSize = $gridSize = $this->app->getGridSize();

        $suggestions = [];
        $suggestionCount = 0;

        switch($filterType) {
            case 'null':
                $suggestions = $this->app->suggestionRepository->getOpenSuggestionsForList($gridSize, ($gridSize * $page));
                $suggestionCount = count($this->app->suggestionRepository->getOpenSuggestionsForList(0, 0));
                break;
    
            case 'category':
                $suggestions = $this->app->suggestionRepository->getOpenSuggestionsForListFilterCategory($filterKey, $gridSize, ($gridSize * $page));
                $suggestionCount = count($this->app->suggestionRepository->getOpenSuggestionsForListFilterCategory($filterKey, 0, 0));
                break;
    
            case 'status':
                $suggestions = $this->app->suggestionRepository->getSuggestionsForListFilterStatus($filterKey, $gridSize, ($gridSize * $page));
                $suggestionCount = count($this->app->suggestionRepository->getSuggestionsForListFilterStatus($filterKey, 0, 0));
                break;
    
            case 'user':
                $suggestions = $this->app->suggestionRepository->getOpenSuggestionsForListFilterAuthor($filterKey, $gridSize, ($gridSize * $page));
                $suggestionCount = count($this->app->suggestionRepository->getOpenSuggestionsForListFilterAuthor($filterKey, 0, 0));
                break;
        }

        $lastPage = ceil($suggestionCount / $gridSize);

        $gb = new GridBuilder();

        $gb->addDataSource($suggestions);
        $gb->addColumns(['title' => 'Title', 'text' => 'Text', 'category' => 'Category', 'status' => 'Status', 'user' => 'User']);
        $gb->addOnColumnRender('text', function(Cell $cell, UserSuggestionEntity $e) {
            return $e->getShortenedText(100);
        });
        $gb->addOnColumnRender('category', function(Cell $cell, UserSuggestionEntity $e) {
            $a = HTML::a();

            $a->onClick('getSuggestionsGrid(-1, \'category\', \'' . $e->getCategory() . '\')')
                ->text(SuggestionCategory::toString($e->getCategory()))
                ->class('grid-link')
                ->style(['color' => SuggestionCategory::getColorByKey($e->getCategory()), 'cursor' => 'pointer'])
                ->href('#')
            ;

            return $a->render();
        });
        $gb->addOnColumnRender('status', function(Cell $cell, UserSuggestionEntity $e) {
            $a = HTML::a();

            $a->onClick('getSuggestionsGrid(-1, \'status\', \'' . $e->getStatus() . '\')')
                ->text(SuggestionStatus::toString($e->getStatus()))
                ->class('grid-link')
                ->style(['color' => SuggestionStatus::getColorByStatus($e->getStatus()), 'cursor' => 'pointer'])
                ->href('#')
            ;

            return $a->render();
        });
        $gb->addOnColumnRender('user', function(Cell $cell, UserSuggestionEntity $e) {
            $user = $this->app->userRepository->getUserById($e->getUserId());

            $a = HTML::a();

            $a->onClick('getSuggestionsGrid(-1, \'user\', \'' . $e->getUserId() . '\')')
                ->text($user->getUsername())
                ->class('grid-link')
                ->href('#')
            ;

            return $a->render();
        });
        $gb->addOnColumnRender('title', function(Cell $cell, UserSuggestionEntity $e) {
            $a = HTML::a();

            $a->text($e->getTitle())
                ->class('grid-link')
                ->href($this->app->composeURL(['page' => 'AdminModule:FeedbackSuggestions', 'action' => 'profile', 'suggestionId' => $e->getId()]))
            ;

            return $a->render();
        });
        $gb->addGridPaging($page, $lastPage, $gridSize, $suggestionCount, 'getSuggestionsGrid', [$filterType, $filterKey]);


        $filterControl = '';
        if($filterType != 'null') {
            /** FILTER CATEGORIES */
            $filterCategories = [
                'all' => 'All',
                'category' => 'Category',
                'status' => 'Status',
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
                case 'category':
                    foreach(SuggestionCategory::getAll() as $k => $v) {
                        if($filterKey == $k) {
                            $options[] = '<option value="' . $k . '" selected>' . $v . '</option>';
                        } else {
                            $options[] = '<option value="' . $k . '">' . $v . '</option>';
                        }
                    }
                    break;

                case 'status':
                    foreach(SuggestionStatus::getAll() as $k => $v) {
                        if($filterKey == $k) {
                            $options[] = '<option value="' . $k . '" selected>' . $v . '</option>';
                        } else {
                            $options[] = '<option value="' . $k . '">' . $v . '</option>';
                        }
                    }
                    break;

                case 'user':
                    $usersInSuggestions = $this->app->suggestionRepository->getUsersInSuggestions();
                    $users = $this->app->userRepository->getUsersByIdBulk($usersInSuggestions);

                    foreach($users as $user) {
                        if($user->getId() == $filterKey) {
                            $options[] = '<option value="' . $user->getId() . '" selected>'. $user->getUsername() . '</option>';
                        } else {
                            $options[] = '<option value="' . $user->getId() . '">'. $user->getUsername() . '</option>';
                        }
                    }

                    break;
            }

            $filterSubcategoriesSelect .= implode('', $options);

            $filterSubcategoriesSelect .= '</select>';
            /** END OF FILTER SUBCATEGORIES */

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
                'category' => 'Category',
                'status' => 'Status',
                'user' => 'User'
            ];
            $filterCategoriesSelect = '<select name="filter-category" id="filter-category" onchange="handleFilterCategoryChange()">';
            foreach($filterCategories as $k => $v) {
                $filterCategoriesSelect .= '<option value="' . $k . '">' . $v . '</option>';
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

            $filterControl = $filterForm . '<script type="text/javascript" src="js/FeedbackSuggestionsFilterHandler.js"></script><script type="text/javascript">$("#filter-subcategory").hide();$("#filter-submit").hide();</script>';
        }

        return ['grid' => $gb->build(), 'filterControl' => $filterControl];
    }

    public function actionGetFilterCategorySuboptions() {
        $category = $this->httpGet('category');

        $options = [];
        switch($category) {
            case 'category':
                foreach(SuggestionCategory::getAll() as $k => $v) {
                    $options[] = '<option value="' . $k . '">' . $v . '</option>';
                }
                break;

            case 'status':
                foreach(SuggestionStatus::getAll() as $k => $v) {
                    $options[] = '<option value="' . $k . '">' . $v . '</option>';
                }
                break;

            case 'user':
                $usersInSuggestions = $this->app->suggestionRepository->getUsersInSuggestions();
                $users = $this->app->userRepository->getUsersByIdBulk($usersInSuggestions);

                foreach($users as $user) {
                    $options[] = '<option value="' . $user->getId() . '">'. $user->getUsername() . '</option>';
                }

                break;
        }

        return ['options' => $options, 'empty' => (empty($options))];
    }
    
    public function handleList() {
        $filterType = $this->httpGet('filterType') ?? 'null';
        $filterKey = $this->httpGet('filterKey') ?? 'null';

        $arb = new AjaxRequestBuilder();

        $arb->setMethod('GET')
            ->setURL(['page' => 'AdminModule:FeedbackSuggestions', 'action' => 'suggestionsListGrid'])
            ->setHeader(['gridPage' => '_page', 'filterType' => '_filterType', 'filterKey' => '_filterKey'])
            ->setFunctionName('getSuggestionsGrid')
            ->setFunctionArguments(['_page', '_filterType', '_filterKey'])
            ->updateHTMLElement('grid-content', 'grid')
            ->updateHTMLElement('filter-control', 'filterControl')
        ;

        $this->addScript($arb->build());
        $this->addScript('getSuggestionsGrid(0, \'' . $filterType . '\', \'' . $filterKey . '\')');
    }

    public function renderList() {}

    public function actionLoadComments() {
        $suggestionId = $this->httpGet('suggestionId');
        $limit = $this->httpGet('limit');
        $offset = $this->httpGet('offset');

        $comments = $this->app->suggestionRepository->getCommentsForSuggestion($suggestionId, $limit, $offset);
        $commentCount = $this->app->suggestionRepository->getCommentCountForSuggestion($suggestionId);

        $loadMoreLink = '';

        if(empty($comments)) {
            return ['comments' => 'No comments', 'loadMoreLink' => $loadMoreLink];
        }

        $commentCode = [];
        foreach($comments as $comment) {
            if($comment->isAdminOnly() && !$this->getUser()->isAdmin()) continue;
            
            $author = $this->app->userRepository->getUserById($comment->getUserId());
            $authorLink = '<a class="post-data-link" href="?page=UserModule:Users&action=profile&userId=' . $comment->getUserId() . '">' . $author->getUsername() . '</a>';

            $hiddenLink = '<a class="post-data-link" style="color: grey" href="?page=AdminModule:FeedbackSuggestions&action=updateComment&commentId=' . $comment->getId() . '&hidden=0&suggestionId=' . $suggestionId . '">Hidden</a>';
            $publicLink = '<a class="post-data-link" style="color: grey" href="?page=AdminModule:FeedbackSuggestions&action=updateComment&commentId=' . $comment->getId() . '&hidden=1&suggestionId=' . $suggestionId . '">Public</a>';
            $hide = $comment->isAdminOnly() ? $hiddenLink : $publicLink;

            $deleteLink = ' <a class="post-data-link" style="color: red" href="?page=AdminModule:FeedbackSuggestions&action=deleteComment&commentId=' . $comment->getId() . '&suggestionId=' . $suggestionId . '">Delete</a>';
            $delete = ($this->getUser()->isAdmin() && !$comment->isStatusChange()) ? $deleteLink : '';

            $tmp = '
                <div id="comment-' . $comment->getId() . '">
                    <p class="post-data">' . $comment->getText() . '</p>
                    <p class="post-data">Author: ' . $authorLink . ' Date: ' . DateTimeFormatHelper::formatDateToUserFriendly($comment->getDateCreated()) . ' ' . $hide . '' . $delete . '</p>
                </div>
            ';

            $commentCode[] = $tmp;
        }

        if(($offset + $limit) < $commentCount) {
            $loadMoreLink = '<a class="post-data-link" onclick="loadSuggestionComments(' . $suggestionId . ', ' . $limit . ', ' . ($limit + $offset) . ')" href="#">Load more</a>';
        }

        return ['comments' => implode('<hr>', $commentCode), 'loadMoreLink' => $loadMoreLink];
    }

    public function handleProfile() {
        $suggestionId = $this->httpGet('suggestionId', true);
        $suggestion = $this->app->suggestionRepository->getSuggestionById($suggestionId);

        $this->saveToPresenterCache('suggestion', $suggestion);

        $author = $this->app->userRepository->getUserById($suggestion->getUserId());
        $authorLink = '<a class="post-data-link" href="?page=UserModule:Users&action=profile&userId=' . $suggestion->getUserId() . '">' . $author->getUsername() . '</a>';
        $status = '<span style="color: ' . SuggestionStatus::getColorByStatus($suggestion->getStatus()) . '">' . SuggestionStatus::toString($suggestion->getStatus()) . '</span>';
        $category = '<span style="color: ' . SuggestionCategory::getColorByKey($suggestion->getCategory()) . '">' . SuggestionCategory::toString($suggestion->getCategory()) . '</span>';

        $dataCode = 'Author: ' . $authorLink . ' Status: ' . $status . ' Category: ' . $category;

        $this->saveToPresenterCache('data', $dataCode);

        $arb = new AjaxRequestBuilder();

        $arb->setURL(['page' => 'AdminModule:FeedbackSuggestions', 'action' => 'loadComments'])
            ->setMethod('GET')
            ->setHeader(['suggestionId' => '_suggestionId', 'limit' => '_limit', 'offset' => '_offset'])
            ->setFunctionName('loadSuggestionComments')
            ->setFunctionArguments(['_suggestionId', '_limit', '_offset'])
            ->updateHTMLElement('comments', 'comments', true)
            ->updateHTMLElement('comments-load-more-link', 'loadMoreLink')
        ;

        $this->addScript($arb->build());
        $this->addScript('loadSuggestionComments(' . $suggestionId . ', 10, 0)');

        $fb = new FormBuilder();
        $fb ->setAction(['page' => 'AdminModule:FeedbackSuggestions', 'action' => 'newComment', 'suggestionId' => $suggestionId])
            ->addTextArea('text', 'Text:', null, true)
        ;

        if($this->getUser()->isAdmin()) {
            $fb->addCheckbox('adminOnly', 'Admin only?', true);
        }

        $fb->addSubmit('Post');

        $this->saveToPresenterCache('form', $fb);

        $adminPart = '';

        if($this->getUser()->isAdmin()) {
            $adminPart = '
                <div class="row">
                    <div class="col-md">
                        Admin actions:
                        <a class="post-data-link" href="?page=AdminModule:FeedbackSuggestions&action=editForm&suggestionId=' . $suggestionId . '">Edit</a>
                    </div>
                </div>

                <hr>
            ';
        }

        $this->saveToPresenterCache('adminPart', $adminPart);
    }

    public function renderProfile() {
        $suggestion = $this->loadFromPresenterCache('suggestion');
        $data = $this->loadFromPresenterCache('data');
        $form = $this->loadFromPresenterCache('form');
        $adminPart = $this->loadFromPresenterCache('adminPart');

        $this->template->title = $suggestion->getTitle();
        $this->template->description = $suggestion->getText();
        $this->template->data = $data;
        $this->template->comment_form = $form;
        $this->template->admin_part = $adminPart;
    }

    public function handleNewComment(?FormResponse $fr = null) {
        $userId = $this->getUserId();
        $suggestionId = $this->httpGet('suggestionId');
        $text = $fr->text;
        $adminOnly = false;

        if($this->httpPost('adminOnly') !== null &&
           $this->httpPost('adminOnly') == 'on' &&
           $this->getUser()->isAdmin()) {
            $adminOnly = true;
        }

        try {
            $this->app->suggestionRepository->beginTransaction();

            $commentId = $this->app->entityManager->generateEntityId(EntityManager::SUGGESTION_COMMENTS);

            $this->app->suggestionRepository->createNewComment($commentId, $userId, $suggestionId, $text, $adminOnly);

            $this->app->suggestionRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Comment posted.', 'success');
        } catch(AException $e) {
            $this->app->suggestionRepository->rollback();

            $this->flashMessage('Could not create comment. Reason: ' . $e->getMessage(), 'error');
        }

        $this->redirect(['page' => 'AdminModule:FeedbackSuggestions', 'action' => 'profile', 'suggestionId' => $suggestionId]);
    }

    public function handleEditForm(?FormResponse $fr = null) {
        $suggestionId = $this->httpGet('suggestionId', true);
        $suggestion = $this->app->suggestionRepository->getSuggestionById($suggestionId);

        $this->saveToPresenterCache('suggestion', $suggestion);

        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $userId = $this->getUserId();
            $user = $this->getUser();
            $category = $fr->category;
            $status = $fr->status;

            $values = [];

            if($category != $suggestion->getCategory()) {
                $values['category'] = $category;
            }
            if($status != $suggestion->getStatus()) {
                $values['status'] = $status;
            }
            
            try {
                $this->app->suggestionRepository->beginTransaction();

                $this->app->suggestionRepository->updateSuggestion($suggestionId, $userId, $values, $user);

                $this->app->suggestionRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Updated suggestion.', 'success');
            } catch(AException $e) {
                $this->app->suggestionRepository->rollback();

                $this->flashMessage('Could not update suggestion. Reason: ' . $e->getMessage(), 'error');
            }

            $this->redirect(['page' => 'AdminModule:FeedbackSuggestions', 'action' => 'profile', 'suggestionId' => $suggestionId]);
        } else {
            $statuses = SuggestionStatus::getAll();
            $statusOptions = [];
            foreach($statuses as $k => $v) {
                $tmp = [
                    'value' => $k,
                    'text' => $v
                ];

                if($suggestion->getStatus() == $k) {
                    $tmp['selected'] = 'selected';
                }

                $statusOptions[] = $tmp;
            }
            
            $categories = SuggestionCategory::getAll();
            $categoryOptions = [];
            foreach($categories as $k => $v) {
                $tmp = [
                    'value' => $k,
                    'text' => $v
                ];

                if($suggestion->getStatus() == $k) {
                    $tmp['selected'] = 'selected';
                }

                $categoryOptions[] = $tmp;
            }

            $fb = new FormBuilder();
            $fb ->setAction(['page' => 'AdminModule:FeedbackSuggestions', 'action' => 'editForm', 'isSubmit' => '1', 'suggestionId' => $suggestionId])
                ->addSelect('status', 'Status:', $statusOptions, true)
                ->addSelect('category', 'Category:', $categoryOptions, true)
                ->addSubmit('Save', false, true)
            ;

            $this->saveToPresenterCache('form', $fb);

            $links = [
                LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('profile', ['suggestionId' => $suggestionId]), 'post-data-link')
            ];

            $this->saveToPresenterCache('links', $links);
        }
    }

    public function renderEditForm() {
        $form = $this->loadFromPresenterCache('form');
        $suggestion = $this->loadFromPresenterCache('suggestion');
        $links = $this->loadFromPresenterCache('links');

        $this->template->form = $form;
        $this->template->suggestion_title = $suggestion->getTitle();
        $this->template->links = $links;
    }

    public function handleUpdateComment() {
        

        $commentId = $this->httpGet('commentId');
        $hidden = $this->httpGet('hidden');
        $suggestionId = $this->httpGet('suggestionId');

        try {
            $this->app->suggestionRepository->beginTransaction();

            $this->app->suggestionRepository->updateComment($commentId, ['adminOnly' => $hidden]);

            $this->app->suggestionRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Comment ' . (($hidden == '1') ? 'hidden' : 'made public') . '.', 'success');
        } catch(AException $e) {
            $this->app->suggestionRepository->rollback();

            $this->flashMessage('Could not update comment. Reason: ' . $e->getMessage(), 'error');
        }

        $this->redirect(['page' => 'AdminModule:FeedbackSuggestions', 'action' => 'profile', 'suggestionId' => $suggestionId]);
    }

    public function handleDeleteComment() {
        

        $commentId = $this->httpGet('commentId');
        $suggestionId = $this->httpGet('suggestionId');

        try {
            $this->app->suggestionRepository->beginTransaction();

            $this->app->suggestionRepository->deleteComment($commentId);

            $this->app->suggestionRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Comment deleted.', 'success');
        } catch(AException $e) {
            $this->app->suggestionRepository->rollback();

            $this->flashMessage('Could not delete comment. Reason: ' . $e->getMessage(), 'error');
        }

        $this->redirect(['page' => 'AdminModule:FeedbackSuggestions', 'action' => 'profile', 'suggestionId' => $suggestionId]);
    }
}

?>