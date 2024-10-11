<?php

namespace App\Modules\AdminModule;

use App\Constants\SuggestionCategory;
use App\Constants\SuggestionStatus;
use App\Core\AjaxRequestBuilder;
use App\Core\DB\DatabaseRow;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\Helpers\DateTimeFormatHelper;
use App\Managers\EntityManager;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use App\UI\GridBuilder2\Cell;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class FeedbackSuggestionsPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('FeedbackSuggestionsPresenter', 'Suggestions');
    }

    public function startup() {
        parent::startup();
        
        if(!$this->app->sidebarAuthorizator->canManageSuggestions($this->getUserId())) {
            $this->flashMessage('You are not authorized to visit this section.');
            $this->redirect(['page' => 'AdminModule:Feedback', 'action' => 'dashboard']);
        }
    }

    public function createComponentGrid(HttpRequest $request) {
        $grid = $this->getGridBuilder();

        $grid->createDataSourceFromQueryBuilder($this->app->suggestionRepository->composeQueryForOpenSuggestions(), 'suggestionId');

        //$grid->addFilter('category', null, SuggestionCategory::getAll());

        $grid->addColumnText('title', 'Title');
        $grid->addColumnUser('userId', 'User');
        $col = $grid->addColumnText('category', 'Category');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            $html->style('color', SuggestionCategory::getColorByKey($value));
            return SuggestionCategory::toString($value);
        };
        $col = $grid->addColumnText('status', 'Status');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            $html->style('color', SuggestionStatus::getColorByStatus($value));
            return SuggestionStatus::toString($value);
        };
        $grid->addColumnDatetime('dateCreated', 'Date created');

        $profile = $grid->addAction('profile');
        $profile->setTitle('Profile');
        $profile->onCanRender[] = function() {
            return true;
        };
        $profile->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            return LinkBuilder::createSimpleLink('Profile', $this->createURL('profile', ['suggestionId' => $primaryKey]), 'grid-link');
        };

        return $grid;
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

            try {
                $author = $this->app->userManager->getUserById($comment->getUserId());
                $authorLink = '<a class="post-data-link" href="?page=UserModule:Users&action=profile&userId=' . $comment->getUserId() . '">' . $author->getUsername() . '</a>';
            } catch(AException $e) {
                $authorLink = '-';
            }

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

        try {
            $author = $this->app->userManager->getUserById($suggestion->getUserId());
            $authorLink = '<a class="post-data-link" href="?page=UserModule:Users&action=profile&userId=' . $suggestion->getUserId() . '">' . $author->getUsername() . '</a>';
        } catch(AException $e) {
            $authorLink = '-';
        }
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