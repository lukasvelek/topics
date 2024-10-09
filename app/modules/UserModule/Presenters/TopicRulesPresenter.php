<?php

namespace App\Modules\UserModule;

use App\Core\AjaxRequestBuilder;
use App\Exceptions\AException;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use App\UI\GridBuilder\Cell;
use App\UI\GridBuilder\GridBuilder;
use App\UI\LinkBuilder;

class TopicRulesPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('TopicRulesPresenter', 'Topic rules');
    }

    public function handleList() {
        $topicId = $this->httpGet('topicId', true);

        $arb = new AjaxRequestBuilder();

        $arb->setMethod()
            ->setHeader(['topicId' => '_topicId'])
            ->setAction($this, 'getTopicRulesListForUser')
            ->setFunctionName('getTopicRules')
            ->setFunctionArguments(['_topicId'])
            ->updateHTMLElement('grid-content', 'grid')
        ;

        if($this->app->actionAuthorizator->canManageTopicRules($this->getUserId(), $topicId) && ($this->httpGet('userList') === null)) {
            $arb->setAction($this, 'getTopicRulesList');
        }

        $this->addScript($arb);
        $this->addScript('getTopicRules(\'' . $topicId . '\')');

        $links = [
            LinkBuilder::createSimpleLink('&larr; Back', ['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topicId], 'post-data-link')
        ];

        if($this->app->actionAuthorizator->canManageTopicRules($this->getUserId(), $topicId)) {
            $links[] = LinkBuilder::createSimpleLink('New rule', ['page' => 'UserModule:TopicRules', 'action' => 'newRuleForm', 'topicId' => $topicId], 'post-data-link');

            if($this->httpGet('userList') !== null) {
                $links[] = LinkBuilder::createSimpleLink('Admin\'s perspective', $this->createURL('list', ['topicId' => $topicId]), 'post-data-link');
            } else {
                $links[] = LinkBuilder::createSimpleLink('User\'s perspective', $this->createURL('list', ['topicId' => $topicId, 'userList' => '1']), 'post-data-link');
            }
        }

        $this->saveToPresenterCache('links', implode('&nbsp;&nbsp;', $links));
    }

    public function renderList() {
        $this->template->links = $this->loadFromPresenterCache('links');
    }

    public function actionGetTopicRulesListForUser() {
        $topicId = $this->httpGet('topicId');

        $rules = $this->app->topicManager->getTopicRulesForTopicId($topicId);

        $code = ['<ol>'];

        $i = 0;
        foreach($rules as $rule) {
            $code[] = '<li>' . $rule . '</li>';

            $i++;
        }

        $code[] = '</ol>';

        return ['grid' => implode('', $code)];
    }

    public function actionGetTopicRulesList() {
        $topicId = $this->httpGet('topicId');

        $rules = $this->app->topicManager->getTopicRulesForTopicId($topicId);

        $grid = $this->getGridBuilder();

        $grid->addColumns(['index' => 'No', 'text' => 'Rule']);
        $grid->addOnColumnRender('index', function(Cell $cell, object $obj) {
            return '#' . ($obj->getIndex() + 1);
        });
        $grid->addDataSourceCallback(function() use ($rules) {
            $entity = function(string $text, int $index) {
                return new class($text, $index) {
                    private int $index;
                    private string $text;

                    public function __construct(string $text, int $index) {
                        $this->text = $text;
                        $this->index = $index;
                    }

                    public function getText() {
                        return $this->text;
                    }

                    public function getIndex() {
                        return $this->index;
                    }
                };
            };

            $tmp = [];
            $i = 0;
            foreach($rules as $rule) {
                $tmp[] = $entity($rule, $i);
                $i++;
            }

            return $tmp;
        });
    
        if($this->app->actionAuthorizator->canManageTopicRules($this->getUserId(), $topicId)) {
            $grid->addAction(function(object $obj) use ($topicId) {
                return LinkBuilder::createSimpleLink('Edit', $this->createURL('editRuleForm', ['topicId' => $topicId, 'index' => $obj->getIndex()]), 'grid-link');
            });
            $grid->addAction(function(object $obj) use ($topicId) {
                return LinkBuilder::createSimpleLink('Delete', $this->createURL('deleteRuleForm', ['topicId' => $topicId, 'index' => $obj->getIndex()]), 'grid-link');
            });
        }

        return ['grid' => $grid->build()];
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
        $index = $this->httpGet('index', true);

        $rules = $this->app->topicManager->getTopicRulesForTopicId($topicId);

        if($index > (count($rules) - 1) || $rules < 0) {
            $this->flashMessage('Incorrect rule selected.', 'error');
            $this->redirect($this->createURL('list', ['topicId' => $topicId]));
        }

        $rule = $rules[$index];

        if($this->httpGet('isFormSubmit') == '1') {
            $ruleText = $fr->ruleText;

            try {
                $this->app->topicRulesRepository->beginTransaction();

                $this->app->topicManager->updateTopicRule($topicId, $this->getUserId(), $index, $ruleText);

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
                ->setAction($this->createURL('editRuleForm', ['topicId' => $topicId, 'index' => $index]))
                ->addTextArea('ruleText', 'Rule text:', $rule, true, 2)
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
        $index = $this->httpGet('index', true);

        if($this->httpGet('isFormSubmit') == '1') {
            try {
                $this->app->topicRulesRepository->beginTransaction();

                $this->app->topicManager->deleteTopicRule($topicId, $this->getUserId(), $index);

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
                ->setAction($this->createURL('deleteRuleForm', ['topicId' => $topicId, 'index' => $index]))
                ->addLabel('Delete topic rule #' . ($index + 1) . '?')
                ->addSubmit('Delete', false, false)
                ->addButton('Go back', 'location.href = \'?page=UserModule:TopicRules&action=list&topicId=' . $topicId . '\'', 'formSubmit')
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