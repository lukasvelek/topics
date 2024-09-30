<?php

namespace App\Modules\UserModule;

use App\Core\AjaxRequestBuilder;
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
    public function __construct() {
        parent::__construct('UserChatsPresenter', 'Chats');
    }

    public function startup() {
        parent::startup();
    }

    public function handleList() {
        $offset = $this->httpGet('offset') ?? '0';

        $links = [
            LinkBuilder::createSimpleLink('New chat', $this->createURL('newChatForm'), 'post-data-link')
        ];

        $this->saveToPresenterCache('links', $links);

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
        $limit = 25;

        $tmp = $this->app->chatManager->getChatsForUser($this->getUserId(), $limit, $offset);
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

                $code .= '<div class="row"><div class="col-md" id="left"><span style="font-size: 14px">' . $tmp . $message->getMessage() . '</span></div></div>';
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

        $users = $this->app->userRepository->searchUsersByUsername($query);

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
            LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('list'), 'post-data-link')
        ];

        $this->saveToPresenterCache('links', $links);

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

        $this->addScript('
            async function autoUpdateMessages(_chatId) {
                await sleep(5000);
                await getChatMessages(_chatId, 0, \'html\');
                await autoUpdateMessages(_chatId);
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
                    const obj = JSON.parse(data);
                    if(_append == "append") {
                        $("#content").append(obj.messages);
                    } else if(_append == "prepend") {
                        $("#content").prepend(obj.messages);
                    } else {
                        $("#content").html(obj.messages);
                        $("#" + obj.lastMessage).get(0).scrollIntoView();
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

        $messages = $this->app->chatManager->getChatMessages($chatId, 20, $offset);

        $lastMessageId = '';
        $code = '';
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
            $code .= 'No messages found.';
        }

        return ['messages' => $code, 'lastMessage' => $lastMessageId];
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
}

?>