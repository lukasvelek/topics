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
        global $app;

        $topicId = $this->httpGet('topicId', true);

        $arb = new AjaxRequestBuilder();

        $arb->setMethod()
            ->setHeader(['topicId' => '_topicId'])
            ->setAction($this, 'getTopicRulesList')
            ->setFunctionName('getTopicRules')
            ->setFunctionArguments(['_topicId'])
            ->updateHTMLElement('grid-content', 'grid')
        ;

        $this->addScript($arb);
        $this->addScript('getTopicRules(\'' . $topicId . '\')');

        $links = [
            LinkBuilder::createSimpleLink('&larr; Back', ['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topicId], 'post-data-link')
        ];

        if($app->actionAuthorizator->canManageTopicRules($app->currentUser->getId(), $topicId)) {
            $links[] = LinkBuilder::createSimpleLink('Manage', $this->createURL('manageRules', ['topicId' => $topicId]), 'post-data-link');
        }

        $this->saveToPresenterCache('links', implode('&nbsp;&nbsp;', $links));
    }

    public function renderList() {
        $this->template->links = $this->loadFromPresenterCache('links');
    }

    public function actionGetTopicRulesList() {
        global $app;

        $topicId = $this->httpGet('topicId');

        $rules = $app->topicManager->getTopicRulesForTopicId($topicId);

        $grid = new GridBuilder();

        $grid->addColumns(['rule' => 'Rule']);
        $grid->addDataSourceCallback(function() use ($rules) {
            $entity = function(string $text) {
                return new class($text) {
                    private string $text;

                    public function __construct(string $text) {
                        $this->text = $text;
                    }

                    public function getText() {
                        return $this->text;
                    }
                };
            };

            $tmp = [];
            foreach($rules as $rule) {
                $tmp[] = $entity($rule);
            }

            return $tmp;
        });

        $this->ajaxSendResponse(['grid' => $grid->build()]);
    }

    public function handleManageRules() {
        $topicId = $this->httpGet('topicId', true);

        $arb = new AjaxRequestBuilder();
        
        $arb->setMethod()
            ->setHeader(['topicId' => '_topicId'])
            ->setAction($this, 'getTopicRulesManagementGrid')
            ->setFunctionName('getManageRulesGrid')
            ->setFunctionArguments(['_topicId'])
            ->updateHTMLElement('grid-content', 'grid')
        ;

        $this->addScript($arb);
        $this->addScript('getManageRulesGrid(\'' . $topicId . '\')');

        $links = [
            LinkBuilder::createSimpleLink('&larr; Back', ['page' => 'UserModule:TopicRules', 'action' => 'list', 'topicId' => $topicId], 'post-data-link'),
            LinkBuilder::createSimpleLink('New rule', ['page' => 'UserModule:TopicRules', 'action' => 'newRuleForm', 'topicId' => $topicId], 'post-data-link')
        ];

        $this->saveToPresenterCache('links', implode('&nbsp;&nbsp;', $links));
    }

    public function renderManageRules() {
        $this->template->links = $this->loadFromPresenterCache('links');
    }

    public function actionGetTopicRulesManagementGrid() {
        global $app;

        $topicId = $this->httpGet('topicId');

        $rules = $app->topicManager->getTopicRulesForTopicId($topicId);

        $grid = new GridBuilder();

        $grid->addColumns(['index' => 'Index', 'text' => 'Rule']);
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
        /*$grid->addAction(function(object $obj) use ($topicId) {
            return LinkBuilder::createSimpleLink('Delete', $this->createURL('deleteRule', ['topicId' => $topicId, 'index' => $obj->getIndex()]), 'grid-link');
        });*/

        $this->ajaxSendResponse(['grid' => $grid->build()]);
    }

    public function handleNewRuleForm(?FormResponse $fr = null) {
        global $app;

        $topicId = $this->httpGet('topicId');

        if($this->httpGet('isFormSubmit') == '1') {
            $ruleText = $fr->ruleText;

            try {
                $app->topicRulesRepository->beginTransaction();

                $app->topicManager->addRuleTextToTopicRules($topicId, $ruleText, $app->currentUser->getId());

                $app->topicRulesRepository->commit($app->currentUser->getId(), __METHOD__);

                $this->flashMessage('New rule added.', 'success');
            } catch(AException $e) {
                $app->topicRulesRepository->rollback();

                $this->flashMessage('Could not add new rule.', 'error');
            }

            $this->redirect($this->createURL('manageRules', ['topicId' => $topicId]));
        } else {
            $links = [
                LinkBuilder::createSimpleLink('&larr; Back', ['page' => 'UserModule:TopicRules', 'action' => 'manageRules', 'topicId' => $topicId], 'post-data-link')
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
}

?>