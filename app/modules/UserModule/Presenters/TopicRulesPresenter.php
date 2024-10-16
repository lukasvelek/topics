<?php

namespace App\Modules\UserModule;

use App\Core\AjaxRequestBuilder;
use App\Core\DB\DatabaseRow;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use App\UI\GridBuilder2\Cell;
use App\UI\GridBuilder2\Row;
use App\UI\GridBuilder\GridBuilder;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class TopicRulesPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('TopicRulesPresenter', 'Topic rules');
    }

    public function startup() {
        parent::startup();
    }

    public function handleList() {
        $topicId = $this->httpGet('topicId', true);

        $links = [
            LinkBuilder::createSimpleLink('&larr; Back', ['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topicId], 'post-data-link')
        ];

        if($this->app->actionAuthorizator->canManageTopicRules($this->getUserId(), $topicId)) {
            $links[] = LinkBuilder::createSimpleLink('New rule', ['page' => 'UserModule:TopicRules', 'action' => 'newRuleForm', 'topicId' => $topicId], 'post-data-link');
        }

        $this->saveToPresenterCache('links', implode('&nbsp;&nbsp;', $links));
    }

    public function renderList() {
        $this->template->links = $this->loadFromPresenterCache('links');
    }

    protected function createComponentGrid(HttpRequest $request) {
        $topicId = $request->query['topicId'];

        $grid = $this->getGridBuilder();

        $grid->createDataSourceFromQueryBuilder($this->app->topicManager->composeQueryForTopicRules($topicId), 'ruleId');

        $grid->addQueryDependency('topicId', $topicId);

        $col = $grid->addColumnText('index', '#');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            return ($_row->index + 1) . '.';
        };

        $grid->addColumnText('ruleText', 'Rule');

        $edit = $grid->addAction('edit');
        $edit->setTitle('Edit');
        $edit->onCanRender[] = function(DatabaseRow $row, Row $_row) use ($topicId) {
            return $this->app->actionAuthorizator->canManageTopicRules($this->getUserId(), $topicId);
        };
        $edit->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) use ($topicId) {
            $el = HTML::el('a')
                    ->href($this->createURLString('editRuleForm', ['ruleId' => $primaryKey, 'topicId' => $topicId]))
                    ->class('grid-link')
                    ->text('Edit');

            return $el;
        };

        $delete = $grid->addAction('delete');
        $delete->setTitle('Delete');
        $delete->onCanRender[] = function(DatabaseRow $row, Row $_row) use ($topicId) {
            return $this->app->actionAuthorizator->canManageTopicRules($this->getUserId(), $topicId);
        };
        $delete->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) use ($topicId) {
            $el = HTML::el('a')
                    ->href($this->createURLString('deleteRuleForm', ['ruleId' => $primaryKey, 'topicId' => $topicId]))
                    ->class('grid-link')
                    ->text('Delete');

            return $el;
        };

        return $grid;
    }

    public function handleNewRuleForm(?FormResponse $fr = null) {
        $topicId = $this->httpGet('topicId', true);

        if($this->httpGet('isFormSubmit') == '1') {
            $ruleText = $fr->ruleText;

            try {
                $this->app->topicRulesRepository->beginTransaction();

                $this->app->topicManager->addRuleTextToTopicRules($topicId, $ruleText, $this->getUserId());

                $this->app->topicRulesRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('New rule added.', 'success');
            } catch(AException $e) {
                $this->app->topicRulesRepository->rollback();

                $this->flashMessage('Could not add new rule.', 'error');
            }

            $this->redirect($this->createURL('list', ['topicId' => $topicId]));
        } else {
            $links = [
                LinkBuilder::createSimpleLink('&larr; Back', ['page' => 'UserModule:TopicRules', 'action' => 'list', 'topicId' => $topicId], 'post-data-link')
            ];

            $this->saveToPresenterCache('links', $links);

            $form = new FormBuilder();

            $form
                ->setMethod()
                ->setAction($this->createURL('newRuleForm', ['topicId' => $topicId]))
                ->addTextArea('ruleText', 'Rule text:', null, true, 2)
                ->addSubmit('Save', false, true)
            ;

            $this->saveToPresenterCache('form', $form);
        }
    }

    public function renderNewRuleForm() {
        $this->template->links = $this->loadFromPresenterCache('links');
        $this->template->form = $this->loadFromPresenterCache('form');
    }

    public function handleEditRuleForm(?FormResponse $fr = null) {
        $topicId = $this->httpGet('topicId', true);
        $ruleId = $this->httpGet('ruleId', true);

        $rule = $this->app->topicManager->getTopicRuleById($ruleId);

        if($this->httpGet('isFormSubmit') == '1') {
            $ruleText = $fr->ruleText;

            try {
                $this->app->topicRulesRepository->beginTransaction();

                $this->app->topicManager->updateTopicRule($this->getUserId(), $ruleId, $ruleText);

                $this->app->topicRulesRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('New rule added.', 'success');
            } catch(AException $e) {
                $this->app->topicRulesRepository->rollback();

                $this->flashMessage('Could not add new rule.', 'error');
            }

            $this->redirect($this->createURL('list', ['topicId' => $topicId]));
        } else {
            $links = [
                LinkBuilder::createSimpleLink('&larr; Back', ['page' => 'UserModule:TopicRules', 'action' => 'list', 'topicId' => $topicId], 'post-data-link')
            ];

            $this->saveToPresenterCache('links', $links);

            $form = new FormBuilder();

            $form
                ->setMethod()
                ->setAction($this->createURL('editRuleForm', ['topicId' => $topicId, 'ruleId' => $ruleId]))
                ->addTextArea('ruleText', 'Rule text:', $rule->getText(), true, 2)
                ->addSubmit('Save', false, true)
            ;

            $this->saveToPresenterCache('form', $form);
        }
    }

    public function renderEditRuleForm() {
        $this->template->links = $this->loadFromPresenterCache('links');
        $this->template->form = $this->loadFromPresenterCache('form');
    }

    public function handleDeleteRuleForm(?FormResponse $fr = null) {
        $topicId = $this->httpGet('topicId', true);
        $ruleId = $this->httpGet('ruleId', true);

        if($this->httpGet('isFormSubmit') == '1') {
            try {
                $this->app->topicRulesRepository->beginTransaction();

                $this->app->topicManager->deleteTopicRule($ruleId, $this->getUserId());

                $this->app->topicRulesRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Rule deleted.', 'success');
            } catch(AException $e) {
                $this->app->topicRulesRepository->rollback();

                $this->flashMessage('Could not delete rule.', 'error');
            }

            $this->redirect($this->createURL('list', ['topicId' => $topicId]));
        } else {
            $links = [];

            $this->saveToPresenterCache('links', $links);

            $form = new FormBuilder();

            $form
                ->setMethod()
                ->setAction($this->createURL('deleteRuleForm', ['topicId' => $topicId, 'ruleId' => $ruleId]))
                ->addLabel('Delete topic rule ?')
                ->addSubmit('Delete', false, false)
                ->addButton('Go back', 'location.href = \'?page=UserModule:TopicRules&action=list&topicId=' . $topicId . '&ruleId=' . $ruleId . '\'', 'formSubmit')
            ;

            $this->saveToPresenterCache('form', $form);
        }
    }

    public function renderDeleteRuleForm() {
        $this->template->form = $this->loadFromPresenterCache('form');
        $this->template->links = $this->loadFromPresenterCache('links');
    }
}

?>