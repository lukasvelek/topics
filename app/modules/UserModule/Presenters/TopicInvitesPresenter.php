<?php

namespace App\Modules\UserModule;

use App\Core\DB\DatabaseRow;
use App\Core\Http\HttpRequest;
use App\Entities\TopicEntity;
use App\Exceptions\AException;
use App\Helpers\GridHelper;
use App\UI\GridBuilder2\Cell;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;
use QueryBuilder\QueryBuilder;

class TopicInvitesPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('TopicInvitesPresenter', 'Topic invites');
    }
    
    public function startup() {
        parent::startup();
    }

    public function renderList() {}

    public function createComponentGrid(HttpRequest $request) {
        $grid = $this->getGridBuilder();

        $grid->createDataSourceFromQueryBuilder($this->app->topicInviteRepository->composeQueryForUserInvites($this->getUserId()), 'inviteId');
        $grid->noFilterSqlConditions[] = function(QueryBuilder &$qb) {
            $qb->andWhere('dateValid > ?', [time()]);
        };
        $grid->setGridName(GridHelper::GRID_TOPIC_INVITES_ALL);

        $col = $grid->addColumnText('topic', 'Topic');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            try {
                $topic = $this->app->topicManager->getTopicById($value, $this->getUserId());

                return TopicEntity::createTopicProfileLink($topic, false, 'grid-link');
            } catch(AException $e) {
                return null;
            }
        };
        $col->onExportColumn[] = function(DatabaseRow $row, mixed $value) {
            try {
                $topic = $this->app->topicManager->getTopicById($value, $this->getUserId());

                return TopicEntity::createTopicProfileLink($topic, false, 'grid-link');
            } catch(AException $e) {
                return null;
            }
        };

        $grid->addColumnDatetime('dateValid', 'Valid until');

        $accept = $grid->addAction('accept');
        $accept->setTitle('Accept');
        $accept->onCanRender[] = function(DatabaseRow $row, Row $_row) {
            return strtotime($row->dateValid) > time();
        };
        $accept->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a')
                ->class('grid-link')
                ->text('Accept')
                ->href($this->createURLString('acceptInvite', ['topicId' => $row->topicId]))
            ;

            return $el;
        };

        $reject = $grid->addAction('reject');
        $reject->setTitle('Reject');
        $reject->onCanRender[] = function(DatabaseRow $row, Row $_row) {
            return strtotime($row->dateValid) > time();
        };
        $reject->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a')
                ->class('grid-link')
                ->text('Reject')
                ->href($this->createURLString('rejectInvite', ['topicId' => $row->topicId]))
            ;

            return $el;
        };

        $delete = $grid->addAction('delete');
        $delete->setTitle('Delete');
        $delete->onCanRender[] = function(DatabaseRow $row, Row $_row) {
            return strtotime($row->dateValid) <= time();
        };
        $delete->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a')
                ->class('grid-link')
                ->text('Delete')
                ->href($this->createURLString('deleteInvite', ['topicId' => $row->topicId]))
            ;

            return $el;
        };

        return $grid;
    }

    public function handleAcceptInvite() {
        $topicId = $this->httpGet('topicId');

        try {
            $this->app->topicRepository->beginTransaction();

            $this->app->topicMembershipManager->acceptInvite($topicId, $this->getUserId());

            $this->app->topicRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Invite accepted.', 'success');
        } catch(AException $e) {
            $this->app->topicRepository->rollback();

            $this->flashMessage('Could not accept invite. Reason: ' . $e->getMessage(), 'error');
        }

        $this->redirect($this->createURL('list'));
    }

    public function handleRejectInvite() {
        $topicId = $this->httpGet('topicId');

        try {
            $this->app->topicRepository->beginTransaction();

            $this->app->topicMembershipManager->rejectInvite($topicId, $this->getUserId());

            $this->app->topicRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Invite rejected.', 'success');
        } catch(AException $e) {
            $this->app->topicRepository->rollback();

            $this->flashMessage('Could not reject invite. Reason: ' . $e->getMessage(), 'error');
        }

        $this->redirect($this->createURL('list'));
    }

    public function handleDeleteInvite() {
        $topicId = $this->httpGet('topicId');

        try {
            $this->app->topicRepository->beginTransaction();

            $this->app->topicMembershipManager->removeInvite($topicId, $this->getUserId());

            $this->app->topicRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Invite deleted.', 'success');
        } catch(AException $e) {
            $this->app->topicRepository->rollback();
            
            $this->flashMessage('Could not delete invite. Reason: ' . $e->getMessage(), 'error');
        }

        $this->redirect($this->createUrl('list'));
    }
}

?>