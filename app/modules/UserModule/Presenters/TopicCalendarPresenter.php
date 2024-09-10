<?php

namespace App\Modules\UserModule;

use App\Core\AjaxRequestBuilder;
use App\Core\Datetypes\DateTime;
use App\Entities\UserEntity;
use App\Exceptions\AException;
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

    public function handleCalendar() {
        global $app;

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

        if($app->actionAuthorizator->canCreateTopicCalendarUserEvents($app->currentUser->getId(), $topicId)) {
            $links[] = LinkBuilder::createSimpleLink('New event', $this->createURL('newEventForm', ['topicId' => $topicId]), 'post-data-link');
        }

        $this->saveToPresenterCache('links', implode('&nbsp;&nbsp;', $links));
    }

    public function renderCalendar() {
        $this->template->grid_controls = $this->loadFromPresenterCache('gridControls');
        $this->template->links = $this->loadFromPresenterCache('links');
    }

    public function actionGetCalendar() {
        global $app;

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
        $posts = $app->postRepository->getScheduledPostsForTopicIdForDate($dateFrom, $dateTo, $topicId);

        $calendar->addEventsFromPosts($posts);
        // End of scheduled posts

        // Polls
        $polls = $app->topicPollRepository->getActivePollsWithValidityForDateRangeForTopicId($topicId, $dateFrom, $dateTo);

        $calendar->addEventsFromPolls($polls);
        // End of polls

        // User events
        $events = $app->topicCalendarEventRepository->getEventsForTopicIdForDateRange($topicId, $dateFrom, $dateTo);

        $calendar->addEventsFromUserEvents($events);
        // End of user events

        $this->ajaxSendResponse(['grid' => $calendar->render(), 'controls' => $calendar->createCalendarControls('getCalendar', [$topicId]), 'info' => $calendar->getCalendarHeader()]);
    }

    public function handleNewEventForm(?FormResponse $fr = null) {
        global $app;

        $topicId = $this->httpGet('topicId', true);

        if($this->httpGet('isFormSubmit') !== null) {
            $title = $fr->title;
            $description = $fr->description;
            $dateFrom = $fr->dateFrom;
            $dateTo = $fr->dateTo;
            $userId = $app->currentUser->getId();

            try {
                $app->topicCalendarEventRepository->beginTransaction();

                $eventId = $app->topicCalendarEventRepository->createEntityId(EntityManager::TOPIC_CALENDAR_USER_EVENTS);

                $app->topicCalendarEventRepository->createEvent($eventId, $userId, $topicId, $title, $description, $dateFrom, $dateTo);

                $app->topicCalendarEventRepository->commit($userId, __METHOD__);

                $this->flashMessage('New event created.', 'success');
            } catch(AException $e) {
                $app->topicCalendarEventRepository->rollback();

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
        global $app;

        $topicId = $this->httpGet('topicId', true);
        $eventId = $this->httpGet('eventId', true);

        $event = $app->topicCalendarEventRepository->getEventById($eventId);

        $dateRange = DateTimeFormatHelper::formatDateToUserFriendly($event->getDateFrom()) . ' - ' . DateTimeFormatHelper::formatDateToUserFriendly($event->getDateTo());

        $author = $app->userRepository->getUserById($event->getUserId());

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

        if($app->actionAuthorizator->canEditUserCalendarEvent($app->currentUser->getId(), $topicId, $event)) {
            $links[] = LinkBuilder::createSimpleLink('Edit', $this->createURL('editEventForm', ['topicId' => $topicId, 'eventId' => $eventId]), 'post-data-link');
        }
        if($app->actionAuthorizator->canDeleteUserCalendarEvent($app->currentUser->getId(), $topicId, $event)) {
            $links[] = LinkBuilder::createSimpleLink('Delete', $this->createURL('deleteEvent', ['topicId' => $topicId, 'eventId' => $eventId]), 'post-data-link');
        }

        $this->saveToPresenterCache('links', implode('&nbsp;&nbsp;', $links));
    }

    public function renderEvent() {
        $this->template->links = $this->loadFromPresenterCache('links');
        $this->template->event_content = $this->loadFromPresenterCache('event_content');
    }
}

?>