<?php

namespace App\Modules\UserModule;

use App\Core\AjaxRequestBuilder;
use App\Core\Datetypes\DateTime;
use App\Entities\UserEntity;
use App\Exceptions\AException;
use App\Exceptions\NonExistingEntityException;
use App\Helpers\DateTimeFormatHelper;
use App\Managers\EntityManager;
use App\UI\CalendarBuilder\CalendarBuilder;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use App\UI\LinkBuilder;

class TopicCalendarPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('TopicCalendarPresenter', 'Topic calendar');
    }

    public function startup() {
        parent::startup();
    }

    public function handleCalendar() {
        $topicId = $this->httpGet('topicId', true);

        $arb = new AjaxRequestBuilder();

        $arb->setMethod()
            ->setHeader(['topicId' => '_topicId', 'year' => '_year', 'month' => '_month'])
            ->setAction($this, 'getCalendar')
            ->setFunctionName('getCalendar')
            ->setFunctionArguments(['_topicId', '_year', '_month'])
            ->updateHTMLElement('grid-content', 'grid')
            ->updateHTMLElement('grid-controls', 'controls')
            ->updateHTMLElement('calendar-info', 'info')
        ;

        $this->addScript($arb);
        $this->addScript('getCalendar(\'' . $topicId . '\', ' . date('Y') . ', ' . date('m') . ')');

        $links = [
            LinkBuilder::createSimpleLink('&larr; Back', ['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topicId], 'post-data-link')
        ];

        if($this->app->actionAuthorizator->canCreateTopicCalendarUserEvents($this->getUserId(), $topicId)) {
            $links[] = LinkBuilder::createSimpleLink('New event', $this->createURL('newEventForm', ['topicId' => $topicId]), 'post-data-link');
        }

        $this->saveToPresenterCache('links', implode('&nbsp;&nbsp;', $links));
    }

    public function renderCalendar() {
        $this->template->grid_controls = $this->loadFromPresenterCache('gridControls');
        $this->template->links = $this->loadFromPresenterCache('links');
    }

    public function actionGetCalendar() {
        $topicId = $this->httpGet('topicId', true);

        $month = $this->httpGet('month');
        $year = $this->httpGet('year');

        $calendar = new CalendarBuilder();
        $calendar->setYear($year);
        $calendar->setMonth($month);

        $dateFrom = $year . '-' . $month . '-01 00:00:00';

        $lastDay = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $dateTo = $year . '-' . $month . '-' . $lastDay . ' 00:00:00';

        // Scheduled posts
        $posts = $this->app->postRepository->getScheduledPostsForTopicIdForDate($dateFrom, $dateTo, $topicId);

        $calendar->addEventsFromPosts($posts);
        // End of scheduled posts

        // Polls
        $polls = $this->app->topicPollRepository->getActivePollsWithValidityForDateRangeForTopicId($topicId, $dateFrom, $dateTo);

        $calendar->addEventsFromPolls($polls);
        // End of polls

        // User events
        $events = $this->app->topicCalendarEventRepository->getEventsForTopicIdForDateRange($topicId, $dateFrom, $dateTo);

        $calendar->addEventsFromUserEvents($events);
        // End of user events

        return ['grid' => $calendar->render(), 'controls' => $calendar->createCalendarControls('getCalendar', [$topicId]), 'info' => $calendar->getCalendarHeader()];
    }

    public function handleNewEventForm(?FormResponse $fr = null) {
        $topicId = $this->httpGet('topicId', true);

        if($this->httpGet('isFormSubmit') !== null) {
            $title = $fr->title;
            $description = $fr->description;
            $dateFrom = $fr->dateFrom;
            $dateTo = $fr->dateTo;
            $userId = $this->getUserId();

            try {
                $this->app->topicCalendarEventRepository->beginTransaction();

                $eventId = $this->app->topicCalendarEventRepository->createEntityId(EntityManager::TOPIC_CALENDAR_USER_EVENTS);

                $this->app->topicCalendarEventRepository->createEvent($eventId, $userId, $topicId, $title, $description, $dateFrom, $dateTo);

                $this->app->topicCalendarEventRepository->commit($userId, __METHOD__);

                $this->flashMessage('New event created.', 'success');
            } catch(AException $e) {
                $this->app->topicCalendarEventRepository->rollback();

                $this->flashMessage('Could not create a new event. Reason: ' . $e->getMessage(), 'error');
            }

            $this->redirect($this->createURL('calendar', ['topicId' => $topicId]));
        } else {
            $links = [
                LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('calendar', ['topicId' => $topicId]), 'post-data-link')
            ];

            $this->saveToPresenterCache('links', $links);

            $date = new DateTime();
            $date->format('Y-m-d H:i');
            $date = $date->getResult();

            $form = new FormBuilder();

            $form->setMethod()
                ->setAction($this->createURL('newEventForm', ['topicId' => $topicId]))
                ->addTextInput('title', 'Title', null, true)
                ->addTextArea('description', 'Description', null, true)
                ->addDatetime('dateFrom', 'Date from', $date, true)
                ->addDatetime('dateTo', 'Date to', $date, false)
                ->addSubmit('Create', false, true)
            ;

            $this->saveToPresenterCache('form', $form);
        }
    }

    public function renderNewEventForm() {
        $this->template->links = $this->loadFromPresenterCache('links');
        $this->template->form = $this->loadFromPresenterCache('form');
    }

    public function handleEvent() {
        $topicId = $this->httpGet('topicId', true);
        $eventId = $this->httpGet('eventId', true);

        $event = $this->app->topicCalendarEventRepository->getEventById($eventId);

        $dateRange = DateTimeFormatHelper::formatDateToUserFriendly($event->getDateFrom()) . ' - ' . DateTimeFormatHelper::formatDateToUserFriendly($event->getDateTo());

        try {
            $author = $this->app->userManager->getUserById($event->getUserId());
        } catch(AException $e) {
            $this->flashMessage('Could not find user. Reason: ' . $e->getMessage(), 'error');
            $this->redirect(['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topicId]);
        }

        if($author !== null) {
            $author = UserEntity::createUserProfileLink($author);
        } else {
            $author = '-';
        }

        $code = '
            <div id="center">
                <h3>' . $event->getTitle() . '</h3>
            </div>
            <br>
            <p><b>Date:</b> ' . $dateRange . '</p>
            <p><b>Description:</b> ' . $event->getDescription() . '</p>
            <p><b>Author:</b> ' . $author . '</p>
        ';

        $this->saveToPresenterCache('event_content', $code);

        $links = [
            LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('calendar', ['topicId' => $topicId]), 'post-data-link')
        ];

        if($this->app->actionAuthorizator->canEditUserCalendarEvent($this->getUserId(), $topicId, $event)) {
            $links[] = LinkBuilder::createSimpleLink('Edit', $this->createURL('editEventForm', ['topicId' => $topicId, 'eventId' => $eventId]), 'post-data-link');
        }
        if($this->app->actionAuthorizator->canDeleteUserCalendarEvent($this->getUserId(), $topicId, $event)) {
            $links[] = LinkBuilder::createSimpleLink('Delete', $this->createURL('deleteEvent', ['topicId' => $topicId, 'eventId' => $eventId]), 'post-data-link');
        }

        $this->saveToPresenterCache('links', implode('&nbsp;&nbsp;', $links));
    }

    public function renderEvent() {
        $this->template->links = $this->loadFromPresenterCache('links');
        $this->template->event_content = $this->loadFromPresenterCache('event_content');
    }

    public function handleEditEventForm(?FormResponse $fr = null) {
        $topicId = $this->httpGet('topicId', true);
        $eventId = $this->httpGet('eventId', true);

        try {
            $event = $this->app->topicCalendarEventRepository->getEventById($eventId);

            if($event === null) {
                throw new NonExistingEntityException('Event #' . $eventId . ' does not exist.');
            }
        } catch(AException $e) {
            $this->flashMessage($e->getMessage(), 'error');
            $this->redirect(['page' => 'UserModule:Home', 'action' => 'dashboard']);
        }

        if($this->httpGet('isFormSubmit') !== null) {
            $title = $fr->title;
            $description = $fr->description;
            $dateFrom = $fr->dateFrom;
            $dateTo = $fr->dateTo;
            $userId = $this->getUserId();

            try {
                $this->app->topicCalendarEventRepository->beginTransaction();

                $data = [
                    'title' => $title,
                    'description' => $description,
                    'dateFrom' => $dateFrom,
                    'dateTo' => $dateTo
                ];

                $this->app->topicCalendarEventRepository->updateEvent($eventId, $data);

                $this->app->topicCalendarEventRepository->commit($userId, __METHOD__);

                $this->flashMessage('Event updated.', 'success');
            } catch(AException $e) {
                $this->app->topicCalendarEventRepository->rollback();

                $this->flashMessage('Could not edit event. Reason: ' . $e->getMessage(), 'error');
            }

            $this->redirect($this->createURL('event', ['topicId' => $topicId, 'eventId' => $eventId]));
        } else {
            $links = [
                LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('event', ['topicId' => $topicId, 'eventId' => $eventId]), 'post-data-link')
            ];

            $this->saveToPresenterCache('links', $links);

            $dateFrom = new DateTime(strtotime($event->getDateFrom()));
            $dateFrom->format('Y-m-d H:i');
            $dateFrom = $dateFrom->getResult();

            $dateTo = new DateTime(strtotime($event->getDateTo()));
            $dateTo->format('Y-m-d H:i');
            $dateTo = $dateTo->getResult();

            $form = new FormBuilder();

            $form->setMethod()
                ->setAction($this->createURL('editEventForm', ['topicId' => $topicId, 'eventId' => $eventId]))
                ->addTextInput('title', 'Title', $event->getTitle(), true)
                ->addTextArea('description', 'Description', $event->getDescription(), true)
                ->addDatetime('dateFrom', 'Date from', $dateFrom, true)
                ->addDatetime('dateTo', 'Date to', $dateTo, false)
                ->addSubmit('Save', false, true)
            ;

            $this->saveToPresenterCache('form', $form);
        }
    }

    public function renderEditEventForm() {
        $this->template->links = $this->loadFromPresenterCache('links');
        $this->template->form = $this->loadFromPresenterCache('form');
    }

    public function handleDeleteEvent() {
        $topicId = $this->httpGet('topicId', true);
        $eventId = $this->httpGet('eventId', true);

        try {
            $this->app->topicCalendarEventRepository->beginTransaction();

            $this->app->topicCalendarEventRepository->deleteEvent($eventId);

            $this->app->topicCalendarEventRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Event deleted.', 'success');
            $this->redirect($this->createUrl('calendar', ['topicId' => $topicId]));
        } catch(AException $e) {
            $this->app->topicCalendarEventRepository->rollback();
            
            $this->flashMessage('Could not delete event. Reason: ' . $e->getMessage(), 'error');
            $this->redirect($this->createURL('event', ['topicId' => $topicId, 'eventId' => $eventId]));
        }

    }
}

?>