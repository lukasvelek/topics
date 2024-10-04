<?php

namespace App\Modules\UserModule;

use App\Core\AjaxRequestBuilder;
use App\Core\Datetypes\DateTime;
use App\Entities\TopicBroadcastChannelMessageEntity;
use App\Entities\TopicEntity;
use App\Entities\UserChatMessageEntity;
use App\Entities\UserEntity;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Helpers\DateTimeFormatHelper;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use App\UI\FormBuilder\TextArea;
use App\UI\LinkBuilder;

class UserChatsPresenter extends AUserPresenter {
    private const CHATS_LIMIT = 25;

    public function __construct() {
        parent::__construct('UserChatsPresenter', 'Chats');
    }

    public function startup() {
        parent::startup();
    }

    public function handleListTopicChannels() {
        $offset = $this->httpGet('offset') ?? '0';

        $links = [
            LinkBuilder::createSimpleLink('User chats', $this->createURL('list'), 'post-data-link')
        ];

        $this->saveToPresenterCache('links', implode('&nbsp;&nbsp;', $links));

        $arb = new AjaxRequestBuilder();

        $arb->setMethod()
            ->setAction($this, 'getTopicChannelList')
            ->setHeader(['offset' => '_offset'])
            ->setFunctionName('getTopicChannelList')
            ->setFunctionArguments(['_offset'])
            ->updateHTMLElement('content', 'list', true);

        $this->addScript($arb);
        $this->addScript('getTopicChannelList(' . $offset . ')');
    }

    public function renderListTopicChannels() {
        $this->template->links = $this->loadFromPresenterCache('links');
    }

    public function actionGetTopicChannelList() {
        $offset = $this->httpGet('offset', true);
        
        $tmp = $this->app->chatManager->getTopicBroadcastChannelsForUser($this->getUserId(), self::CHATS_LIMIT, $offset);
        $channels = $tmp['channels'];
        /** @var array<\App\Entities\TopicBroadcastChannelMessageEntity> */
        $lastMessages = $tmp['lastMessages'];

        $code = '<div>';
        foreach($channels as $channel) {
            $topic = $this->app->topicManager->getTopicById($channel->getTopicId(), $this->getUserId());
            $code .= '<a class="post-data-link" href="' . $this->createFullURLString('UserModule:UserChats', 'channel', ['channelId' => $channel->getChannelId()]) . '"><div class="row" id="channel-id-' . $channel->getChannelId() . '">';
            $code .= '<div class="col-md">';
            $code .= '<div class="row">';
            $code .= '<div class="col-md" id="left">';
            $code .= '<span style="font-size: 18px">' . $topic->getTitle() . '</span>';
            $code .= '</div>';
            $code .= '</div>';

            if(array_key_exists($channel->getChannelId(), $lastMessages)) {
                $message = $lastMessages[$channel->getChannelId()];

                $do = new DateTime(strtotime($message->getDateCreated()));
                $format = DateTimeFormatHelper::EUROPEAN_FORMAT;

                $dt = clone($do);
                $dt->format('Y-m-d');
                if($dt->getResult() == date('Y-m-d')) {
                    $format = DateTimeFormatHelper::TIME_ONLY_FORMAT;
                }

                $date = '<span title="' . DateTimeFormatHelper::formatDateToUserFriendly($do, DateTimeFormatHelper::ATOM_FORMAT) . '">' . DateTimeFormatHelper::formatDateToUserFriendly($do, $format) . '</span>';
                $code .= '<div class="row"><div class="col-md" id="left"><span style="font-size: 14px">' . $message->getMessage() . '</span></div><div class="col-md-3" id="right">' . $date . '</div></div>';
            }

            $code .= '</div>';
            $code .= '</div></a>';
        }

        $code .= '</div>';
        
        return ['list' => $code];
    }

    public function handleList() {
        $offset = $this->httpGet('offset') ?? '0';

        $links = [
            LinkBuilder::createSimpleLink('Topic channels', $this->createURL('listTopicChannels'), 'post-data-link'),
            LinkBuilder::createSimpleLink('New chat', $this->createURL('newChatForm'), 'post-data-link')
        ];

        $this->saveToPresenterCache('links', implode('&nbsp;&nbsp;', $links));

        $arb = new AjaxRequestBuilder();
        
        $arb->setMethod()
            ->setAction($this, 'getChatList')
            ->setHeader(['offset' => '_offset'])
            ->setFunctionName('getChatList')
            ->setFunctionArguments(['_offset'])
            ->updateHTMLElement('content', 'list', true);
        ;

        $this->addScript($arb);
        $this->addScript('getChatList(' . $offset . ')');
    }

    public function renderList() {
        $this->template->links = $this->loadFromPresenterCache('links');
    }

    public function actionGetChatList() {
        $offset = $this->httpGet('offset', true);

        $tmp = $this->app->chatManager->getChatsForUser($this->getUserId(), self::CHATS_LIMIT, $offset);
        $chats = $tmp['chats'];
        /** @var array<UserChatMessageEntity> */
        $lastMessages = $tmp['lastMessages'];

        $code = '<div>';

        foreach($chats as $chat) {
            if($this->getUserId() == $chat->getUser1Id()) {
                $username = $this->app->userRepository->getUserById($chat->getUser2Id())->getUsername();
            } else {
                $username = $this->app->userRepository->getUserById($chat->getUser1Id())->getUsername();
            }

            $code .= '<a class="post-data-link" href="' . $this->createFullURLString('UserModule:UserChats', 'chat', ['chatId' => $chat->getChatId()]) . '"><div class="row" id="chat-id-' . $chat->getChatId() . '">';
            $code .= '<div class="col-md">';
            $code .= '<div class="row">';
            $code .= '<div class="col-md" id="left">';
            $code .= '<span style="font-size: 18px">' . $username . '</span>';
            $code .= '</div>';
            $code .= '</div>';
            
            if(array_key_exists($chat->getChatId(), $lastMessages)) {
                $message = $lastMessages[$chat->getChatId()];

                $tmp = '';
                if($message->getAuthorId() != $this->getUserId()) {
                    $otherUser = $this->app->userRepository->getUserById($message->getAuthorId());
                    if($otherUser !== null) {
                        $tmp = $otherUser->getUsername() . ': ';
                    }
                }

                $do = new DateTime(strtotime($message->getDateCreated()));
                $format = DateTimeFormatHelper::EUROPEAN_FORMAT;

                $dt = clone($do);
                $dt->format('Y-m-d');
                if($dt->getResult() == date('Y-m-d')) {
                    $format = DateTimeFormatHelper::TIME_ONLY_FORMAT;
                }

                $date = '<span title="' . DateTimeFormatHelper::formatDateToUserFriendly($do, DateTimeFormatHelper::ATOM_FORMAT) . '">' . DateTimeFormatHelper::formatDateToUserFriendly($do, $format) . '</span>';

                $code .= '<div class="row"><div class="col-md" id="left"><span style="font-size: 14px">' . $tmp . $message->getMessage() . '</span></div><div class="col-md-3" id="right">' . $date . '</div></div>';
            }

            $code .= '</div>';
            $code .= '</div></a>';
        }

        $code .= '</div>';

        return ['list' => $code];
    }

    public function handleNewChatForm(?FormResponse $fr = null) {
        if($this->httpGet('isFormSubmit') == '1') {
            $user = $fr->users;

            try {
                $this->app->chatRepository->beginTransaction();

                $chatId = $this->app->chatManager->createNewChat($this->getUserId(), $user);

                $this->app->chatRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('New chat created.', 'success');
            } catch(AException $e) {
                $this->app->chatRepository->rollback();

                $this->flashMessage('Could not create a new chat. Reason: ' . $e->getMessage(), 'error');
            }

            $this->redirect($this->createURL('chat', ['chatId' => $chatId]));
        } else {
            $links = [
                LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('list'), 'post-data-link')
            ];
    
            $this->saveToPresenterCache('links', $links);
    
            $form = new FormBuilder();
            $form->setAction($this->createURL('newChatForm'));

            $form->addTextInput('userSearchQuery', 'Username:', null, true);
            $form->addButton('Search', 'processSearchUser()', 'formSubmit');

            $form->addSelect('users', 'Users:', [], true);
            $form->addSubmit('Start a chat', true, false, 'formSubmit2');
    
            $this->saveToPresenterCache('form', $form);

            $this->addScript('
                async function processSearchUser() {
                    const val = $("#userSearchQuery").val();
                    if(!val) {
                        alert("No username defined");
                        return;
                    }
                    return await searchUser(val);
                }
            ');

            $arb = new AjaxRequestBuilder();

            $arb->setAction($this, 'searchUser')
                ->setMethod()
                ->setHeader(['query' => '_query'])
                ->setFunctionName('searchUser')
                ->setFunctionArguments(['_query'])
                ->updateHTMLElementRaw('"#users"', 'userList')
                ->addWhenDoneOperation('
                    if(obj.empty == "0") {
                        $(\'input[type="submit"]\').removeAttr("disabled");
                    } else {
                        $(\'input[type="submit"]\').attr("disabled", "disabled");
                    }
                ')
            ;

            $this->addScript($arb);
        }
    }

    public function renderNewChatForm() {
        $this->template->links = $this->loadFromPresenterCache('links');
        $this->template->form = $this->loadFromPresenterCache('form');
    }

    public function actionSearchUser() {
        $query = $this->httpGet('query', true);

        $users = $this->app->chatManager->searchUsersForNewChat($query, $this->getUserId());

        $results = [];
        foreach($users as $user) {
            if($user->getId() == $this->getUserId()) continue;

            $results[] = '<option value="' . $user->getId() . '">' . $user->getUsername() . '</option>';
        }

        return ['userList' => $results, 'empty' => (count($results) > 0) ? '0' : '1'];
    }

    public function handleChat() {
        $chatId = $this->httpGet('chatId', true);
        
        try {
            $chat = $this->app->chatManager->getChatEntityById($chatId);
        } catch(AException $e) {
            $this->flashMessage('Could not find this chat. Reason: ' . $e->getMessage(), 'error');
            $this->redirect($this->createURL('list'));
        }

        $otherUserId = ($chat->getUser1Id() == $this->getUserId()) ? $chat->getUser2Id() : $chat->getUser1Id();
        
        try {
            $otherUser = $this->app->userManager->getUserById($otherUserId);
        } catch(AException $e) {
            $this->flashMessage('Could not find this user. Reason: ' . $e->getMessage(), 'error');
            $this->redirect($this->createURL('list'));
        }

        $this->saveToPresenterCache('user_link', $otherUser->getUsername());

        $links = [
            LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('list'), 'post-data-link'),
            UserEntity::createUserProfileLink($otherUser)
        ];

        $this->saveToPresenterCache('links', implode('&nbsp;&nbsp;', $links));

        $form = new FormBuilder();

        $form->addTextArea('message', 'Message:', null, true)
            ->addButton('Submit', 'processSubmitMessage(\'' . $chatId . '\')', 'formSubmit')
        ;

        $form->updateElement('message', function(TextArea $ta) {
            $ta->setId('message');
            return $ta;
        });

        $this->saveToPresenterCache('form', $form);

        $arb = new AjaxRequestBuilder();
        $arb->setMethod()
            ->setAction($this, 'submitMessage')
            ->setHeader(['chatId' => '_chatId', 'message' => '_message'])
            ->setFunctionName('submitMessage')
            ->setFunctionArguments(['_chatId', '_message'])
            ->updateHTMLElement('content', 'message', true)
            ->addWhenDoneOperation('
                $("#" + obj.messageId).get(0).scrollIntoView();
            ')
        ;

        $this->addScript($arb);
        $this->addScript('
            async function processSubmitMessage(_chatId) {
                const message = $("#message").val();
                await submitMessage(_chatId, message);
                $("#message").val("");
            }
        ');

        $this->addScript('let _autoRun = true;');
        $this->addScript('
            async function autoUpdateMessages(_chatId) {
                if(_autoRun) {
                    await sleep(5000);
                    await getChatMessages(_chatId, 0, \'html_noscroll\');
                    await autoUpdateMessages(_chatId);
                }
            }
        ');

        $arb = new AjaxRequestBuilder();
        $arb->setMethod()
            ->setAction($this, 'getChatMessages')
            ->setHeader(['chatId' => '_chatId', 'offset' => '_offset'])
            ->setFunctionName('getChatMessages')
            ->setFunctionArguments(['_chatId', '_offset', '_append'])
            ->addWhenDoneOperation('
                try {
                    if(!_autoRun) return;
                    const obj = JSON.parse(data);
                    if(_append == "append") {
                        $("#content").append(obj.messages);
                    } else if(_append == "prepend") {
                        $("#content").prepend(obj.messages);
                    } else if(_append == "html_noscroll") {
                        $("#content").html(obj.messages);
                    } else {
                        $("#content").html(obj.messages);
                        $("#" + obj.lastMessage).get(0).scrollIntoView();
                    }
                    
                    if(obj.loadMore == 1) {
                        _autoRun = false;
                    }
                } catch(error) {
                    alert("Could not load data. See console for more information.");
                    console.log(error);
                }
            ')
        ;
        
        $this->addScript($arb);
        $this->addScript('getChatMessages(\'' . $chatId . '\', 0, \'html\'); autoUpdateMessages(\'' . $chatId . '\');');
    }

    public function renderChat() {
        $this->template->links = $this->loadFromPresenterCache('links');
        $this->template->user_link = $this->loadFromPresenterCache('user_link');
        $this->template->form = $this->loadFromPresenterCache('form');
    }

    public function actionGetChatMessages() {
        $chatId = $this->httpGet('chatId', true);
        $offset = $this->httpGet('offset', true);

        $limit = 10;

        $messages = $this->app->chatManager->getChatMessages($chatId, ($limit + $offset), $offset);

        $lastMessageId = '';
        $code = '';
        $ok = true;
        if(!empty($messages)) {
            $messageCode = [];
            $i = 0;
            foreach($messages as $message) {
                if(($i + 1) == count($messages)) {
                    $lastMessageId = 'message-id-' . $message->getMessageId();
                    if($message->getAuthorId() == $this->getUserId()) {
                        $lastMessageId = 'my-' . $lastMessageId;
                    }
                }
                $messageCode[] = $this->createMessageCode($message);
                $i++;
            }
            $code .= implode('<br>', $messageCode);
        } else {
            $ok = false;
            if($offset == 0) {
                $code .= 'No messages found.';
            }
        }

        if($ok) {
            $loadNextLink = '
                <div class="row">
                    <div class="col-md-3"></div>
                    <div class="col-md" id="form">
                        <button type="button" class="formSubmit" id="load-more-offset-' . ($offset + $limit) . '" onclick="getChatMessages(\'' . $chatId . '\', ' . ($offset + $limit) . ', \'prepend\'); $(\'#load-more-offset-' . ($offset + $limit) . '\').remove();">Load more</button>
                    </div>
                    <div class="col-md-3"></div>
                </div>
                ';
            $code = $loadNextLink . '<br>' . $code;
        }

        return ['messages' => $code, 'lastMessage' => $lastMessageId, 'loadMore' => (($offset > 0) ? '1' : '0')];
    }

    public function actionSubmitMessage() {
        $chatId = $this->httpGet('chatId', true);
        $message = $this->httpGet('message', true);

        try {
            $this->app->chatRepository->beginTransaction();

            $messageId = $this->app->chatManager->createNewMessage($chatId, $this->getUserId(), $message);

            if($messageId === null) {
                throw new GeneralException('Could not obtain last created message.');
            }

            $this->app->chatManager->invalidateCache($this->getUserId());

            $this->app->chatRepository->commit($this->getUserId(), __METHOD__);
        } catch(AException $e) {
            $this->app->chatRepository->rollback();
        }

        $message = $this->app->chatManager->getChatMessageEntityById($messageId);

        return ['message' => '<br>' . $this->createMessageCode($message), 'messageId' => 'my-message-id-' . $messageId];
    }

    private function createMessageCode(UserChatMessageEntity $message) {
        $isAuthor = ($message->getAuthorId() == $this->getUserId());

        $messageContent = '
            <span style="font-size: 16px">' . $message->getMessage() . '<span>
            <br>
            <span style="font-size: 12px" title="' . DateTimeFormatHelper::formatDateToUserFriendly($message->getDateCreated(), DateTimeFormatHelper::ATOM_FORMAT) . '">' . DateTimeFormatHelper::formatDateToUserFriendly($message->getDateCreated()) . '</span>
        ';

        $code = '';
        if($isAuthor) {
            $code = '
                <div class="row">
                    <div class="col-md"></div>
                    <div class="col-md-5">
                        <div id="my-message-id-' . $message->getMessageId() . '">
                            ' . $messageContent . '
                        </div>
                    </div>
                </div>
            ';
        } else {
            $code = '
                <div class="row">
                    <div class="col-md-5">
                        <div id="message-id-' . $message->getMessageId() . '">
                            ' . $messageContent . '
                        </div>
                    </div>
                    <div class="col-md"></div>
                </div>
            ';
        }

        return $code;
    }

    public function handleCreateTopicBroadcastChannel() {
        $topicId = $this->httpGet('topicId', true);

        try {
            $this->app->chatRepository->beginTransaction();

            $channelId = $this->app->chatManager->createNewTopicBroadcastChannel($topicId);

            $this->app->chatManager->createNewTopicBroadcastChannelSubscribe($channelId, $this->getUserId());

            $this->app->chatRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('New topic broadcast channel created.', 'success');
            $this->redirect($this->createURL('channel', ['channelId' => $channelId]));
        } catch(AException $e) {
            $this->app->chatRepository->rollback();

            $this->flashMessage('Could not create a new topic broadcast channel. Reason: ' . $e->getMessage(), 'error');
            $this->redirect(['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topicId]);
        }
    }

    public function handleChannel() {
        $channelId = $this->httpGet('channelId');
        $channel = $this->app->chatRepository->getTopicBroadcastChannelById($channelId);

        if($channel === null) {
            $this->redirect($this->createURL('list'));
        }

        $links = [];

        if($this->app->actionAuthorizator->canCreateTopicBroadcastChannelMessage($this->getUserId(), $channel->getTopicId())) {
            $links[] = LinkBuilder::createSimpleLink('New message', $this->createURL('channelNewMessageForm', ['channelId' => $channelId]), 'post-data-link');
        }

        $this->saveToPresenterCache('links', implode('&nbsp;&nbsp;', $links));

        $topic = $this->app->topicRepository->getTopicById($channel->getTopicId());

        $topicLink = TopicEntity::createTopicProfileLink($topic, false, '');

        $this->saveToPresenterCache('topicLink', $topicLink);

        $arb = new AjaxRequestBuilder();
        $arb->setMethod()
            ->setAction($this, 'getChannelMessages')
            ->setHeader(['channelId' => '_channelId', 'offset' => '_offset'])
            ->setFunctionName('getChannelMessages')
            ->setFunctionArguments(['_channelId', '_offset', '_append'])
            ->addWhenDoneOperation('
                if(_append == "append") {
                    $("#content").append(obj.messages);
                } else if(_append == "prepend") {
                    $("#content").prepend(obj.messages);
                } else if(_append == "html_noscroll") {
                    $("#content").html(obj.messages);
                } else {
                    $("#content").html(obj.messages);
                    if(obj.lastMessage) {
                        $("#" + obj.lastMessage).get(0).scrollIntoView();
                    }
                }
            ')
        ;

        $this->addScript($arb);
        $this->addScript('getChannelMessages(\'' . $channelId . '\', 0, \'html\');');
    }

    public function renderChannel() {
        $this->template->links = $this->loadFromPresenterCache('links');
        $this->template->topic_link = $this->loadFromPresenterCache('topicLink');
    }

    public function actionGetChannelMessages() {
        $channelId = $this->httpGet('channelId', true);
        $offset = $this->httpGet('offset', true);

        $limit = 10;

        $messages = $this->app->chatManager->getTopicBroadcastChannelMessages($channelId, ($limit + $offset), $offset);

        $lastMessageId = '';
        $code = '';
        $ok = true;
        if(!empty($messages)) {
            $messageCode = [];
            $i = 0;
            foreach($messages as $message) {
                if(($i + 1) == count($messages)) {
                    $lastMessageId = 'message-id-' . $message->getMessageId();
                    if($message->getAuthorId() == $this->getUserId()) {
                        $lastMessageId = 'my-' . $lastMessageId;
                    }
                }
                $messageCode[] = $this->createChannelMessageCode($message);
                $i++;
            }
            $code .= implode('<br>', $messageCode);
        } else {
            $ok = false;
            if($offset == 0) {
                $code .= 'No messages found.';
            }
        }

        if($ok && count($messages) >= 10) {
            $loadNextLink = '
                <div class="row">
                    <div class="col-md-3"></div>
                    <div class="col-md" id="form">
                        <button type="button" class="formSubmit" id="load-more-offset-' . ($offset + $limit) . '" onclick="getChannelMessages(\'' . $channelId . '\', ' . ($offset + $limit) . ', \'prepend\'); $(\'#load-more-offset-' . ($offset + $limit) . '\').remove();">Load more</button>
                    </div>
                    <div class="col-md-3"></div>
                </div>
                ';
            $code = $loadNextLink . '<br>' . $code;
        }

        return ['messages' => $code, 'lastMessage' => $lastMessageId, 'loadMore' => (($offset > 0) ? '1' : '0')];
    }

    private function createChannelMessageCode(TopicBroadcastChannelMessageEntity $message) {
        $isAuthor = ($message->getAuthorId() == $this->getUserId());

        $messageContent = '
            <span style="font-size: 16px">' . $message->getMessage() . '<span>
            <br>
            <span style="font-size: 12px" title="' . DateTimeFormatHelper::formatDateToUserFriendly($message->getDateCreated(), DateTimeFormatHelper::ATOM_FORMAT) . '">' . DateTimeFormatHelper::formatDateToUserFriendly($message->getDateCreated()) . '</span>
        ';

        $code = '';
        if($isAuthor) {
            $code = '
                <div class="row">
                    <div class="col-md"></div>
                    <div class="col-md-5">
                        <div id="my-message-id-' . $message->getMessageId() . '">
                            ' . $messageContent . '
                        </div>
                    </div>
                </div>
            ';
        } else {
            $code = '
                <div class="row">
                    <div class="col-md-5">
                        <div id="message-id-' . $message->getMessageId() . '">
                            ' . $messageContent . '
                        </div>
                    </div>
                    <div class="col-md"></div>
                </div>
            ';
        }

        return $code;
    }

    public function handleChannelNewMessageForm(?FormResponse $fr = null) {
        $channelId = $this->httpGet('channelId', true);

        if($this->httpGet('isFormSubmit') == '1') {
            try {
                $this->app->chatRepository->beginTransaction();

                $message = $fr->message;

                $this->app->chatManager->createNewTopicBroadcastChannelMessage($channelId, $this->getUserId(), $message);

                $this->app->chatRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('New message posted.', 'success');
            } catch(AException $e) {
                $this->app->chatRepository->rollback();

                $this->flashMessage('Could not create a new message. Reason: ' . $e->getMessage(), 'error');
            }

            $this->redirect($this->createURL('channel', ['channelId' => $channelId]));
        } else {
            $links = [
                LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('channel', ['channelId' => $channelId]), 'post-data-link')
            ];
    
            $this->saveToPresenterCache('links', implode('&nbsp;&nbsp;', $links));
    
            $form = new FormBuilder();
            $form->setMethod()
                ->setAction($this->createURL('channelNewMessageForm', ['channelId' => $channelId]))
                ->addTextArea('message', 'Message:', null, true)
                ->addSubmit('Post new message', false, true)
            ;
    
            $this->saveToPresenterCache('form', $form);
        }
    }

    public function renderChannelNewMessageForm() {
        $this->template->links = $this->loadFromPresenterCache('links');
        $this->template->form = $this->loadFromPresenterCache('form');
    }
}

?>