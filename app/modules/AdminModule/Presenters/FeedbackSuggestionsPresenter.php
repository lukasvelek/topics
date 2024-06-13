<?php

namespace App\Modules\AdminModule;

use App\Components\Sidebar\Sidebar;
use App\Constants\SuggestionCategory;
use App\Constants\SuggestionStatus;
use App\Constants\SystemStatus;
use App\Modules\APresenter;
use App\UI\FormBuilder\FormBuilder;

class FeedbackSuggestionsPresenter extends APresenter {
    public function __construct() {
        parent::__construct('FeedbackSuggestionsPresenter', 'Suggestions');

        $this->addBeforeRenderCallback(function() {
            $this->template->sidebar = $this->createSidebar();
        });
    }

    private function createSidebar() {
        $sb = new Sidebar();
        $sb->addLink('Dashboard', ['page' => 'AdminModule:Feedback', 'action' => 'dashboard']);
        $sb->addLink('Suggestions', ['page' => 'AdminModule:FeedbackSuggestions', 'action' => 'list'], true);
        $sb->addLink('Reports', ['page' => 'AdminModule:FeedbackReports', 'action' => 'list']);

        return $sb->render();
    }

    public function handleList() {
        global $app;

        $filterType = $this->httpGet('filterType') ?? 'null';
        $filterKey = $this->httpGet('filterKey') ?? 'null';

        $this->saveToPresenterCache('list', '<script type="text/javascript">loadSuggestions(10, 0, ' . $app->currentUser->getId() . ', \'' . $filterType . '\', \'' . $filterKey . '\')</script><div id="suggestion-list"></div><div id="suggestion-list-link"></div><br>');
    }

    public function renderList() {
        $list = $this->loadFromPresenterCache('list');

        $this->template->suggestions = $list;
    }

    public function handleProfile() {
        global $app;

        $suggestionId = $this->httpGet('suggestionId', true);
        $suggestion = $app->suggestionRepository->getSuggestionById($suggestionId);

        $this->saveToPresenterCache('suggestion', $suggestion);

        $author = $app->userRepository->getUserById($suggestion->getUserId());
        $authorLink = '<a class="post-data-link" href="?page=UserModule:Users&action=profile&userId=' . $suggestion->getUserId() . '">' . $author->getUsername() . '</a>';
        $status = '<span style="color: ' . SuggestionStatus::getColorByStatus($suggestion->getStatus()) . '">' . SuggestionStatus::toString($suggestion->getStatus()) . '</span>';
        $category = '<span style="color: ' . SuggestionCategory::getColorByKey($suggestion->getCategory()) . '">' . SuggestionCategory::toString($suggestion->getCategory()) . '</span>';

        $dataCode = 'Author: ' . $authorLink . ' Status: ' . $status . ' Category: ' . $category;

        $this->saveToPresenterCache('data', $dataCode);

        $commentsCode = '
            <script type="text/javascript">loadFeedbackSuggestionComments(' . $suggestionId . ', 10, 0, ' . $app->currentUser->getId() . ')</script><div id="comments-content"></div><div id="comments-load-more"></div>
        ';

        $this->saveToPresenterCache('comments', $commentsCode);

        $fb = new FormBuilder();
        $fb ->setAction(['page' => 'AdminModule:FeedbackSuggestions', 'action' => 'newComment', 'suggestionId' => $suggestionId])
            ->addTextArea('text', 'Text:', null, true)
        ;

        if($app->currentUser->isAdmin()) {
            $fb->addCheckbox('adminOnly', 'Admin only?', true);
        }

        $fb->addSubmit('Post');

        $this->saveToPresenterCache('form', $fb);

        $adminPart = '';

        if($app->currentUser->isAdmin()) {
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
        $comments = $this->loadFromPresenterCache('comments');
        $form = $this->loadFromPresenterCache('form');
        $adminPart = $this->loadFromPresenterCache('adminPart');

        $this->template->title = $suggestion->getTitle();
        $this->template->description = $suggestion->getText();
        $this->template->data = $data;
        $this->template->comments = $comments;
        $this->template->comment_form = $form->render();
        $this->template->admin_part = $adminPart;
    }

    public function handleNewComment() {
        global $app;

        $userId = $app->currentUser->getId();
        $suggestionId = $this->httpGet('suggestionId');
        $text = $this->httpPost('text');
        $adminOnly = false;

        if( $this->httpPost('adminOnly') !== null &&
            ($this->httpPost('adminOnly') == '1' || $this->httpPost('adminOnly') == 1) &&
            $app->currentUser->isAdmin()) {
            $adminOnly = true;
        }

        $app->suggestionRepository->createNewComment($userId, $suggestionId, $text, $adminOnly);

        $this->flashMessage('Comment posted.', 'success');
        $this->redirect(['page' => 'AdminModule:FeedbackSuggestions', 'action' => 'profile', 'suggestionId' => $suggestionId]);
    }

    public function handleEditForm() {
        global $app;

        $suggestionId = $this->httpGet('suggestionId', true);
        $suggestion = $app->suggestionRepository->getSuggestionById($suggestionId);

        $this->saveToPresenterCache('suggestion', $suggestion);

        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $userId = $app->currentUser->getId();
            $user = $app->currentUser;
            $category = $this->httpPost('category');
            $status = $this->httpPost('status');

            $values = [];

            if($category != $suggestion->getCategory()) {
                $values['category'] = $category;
            }
            if($status != $suggestion->getStatus()) {
                $values['status'] = $status;
            }
            
            $app->suggestionRepository->updateSuggestion($suggestionId, $userId, $values, $user);

            $this->flashMessage('Updated suggestion.', 'success');
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
                ->addSubmit('Save')
            ;

            $this->saveToPresenterCache('form', $fb);
        }
    }

    public function renderEditForm() {
        $form = $this->loadFromPresenterCache('form');
        $suggestion = $this->loadFromPresenterCache('suggestion');

        $this->template->form = $form->render();
        $this->template->suggestion_title = $suggestion->getTitle();
    }

    public function handleUpdateComment() {
        global $app;

        $commentId = $this->httpGet('commentId');
        $hidden = $this->httpGet('hidden');
        $suggestionId = $this->httpGet('suggestionId');

        $app->suggestionRepository->updateComment($commentId, ['adminOnly' => $hidden]);

        $this->flashMessage('Comment ' . (($hidden == '1') ? 'hidden' : 'made public') . '.', 'success');
        $this->redirect(['page' => 'AdminModule:FeedbackSuggestions', 'action' => 'profile', 'suggestionId' => $suggestionId]);
    }
}

?>