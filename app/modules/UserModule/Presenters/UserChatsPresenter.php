<?php

namespace App\Modules\UserModule;

use App\Core\AjaxRequestBuilder;
use App\Entities\UserEntity;
use App\Exceptions\AException;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
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

            $code .= '<a class="post-data-link" href="' . $this->createFullURLString('UserModule:UserChats', 'chat', ['chatId' => $chat->getChatId()]) . '"><div id="chat-id-' . $chat->getChatId() . '">';
            $code .= '<span>' . $username . '</span>';
            
            if(array_key_exists($chat->getChatId(), $lastMessages)) {
                $message = $lastMessages[$chat->getChatId()];

                $code .= $message->getMessage();
            }

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

        $links = [];

        $this->saveToPresenterCache('links', $links);

        $form = new FormBuilder();

        $form->addTextArea('message', 'Message:', null, true)
            ->addButton('Submit', 'processSubmitMessage(\'' . $chatId . '\')', 'formSubmit')
        ;

        $this->saveToPresenterCache('form', $form);

        $arb = new AjaxRequestBuilder();
        $arb->setMethod()
            ->setAction($this, 'submitMessage')
            ->setHeader(['chatId' => '_chatId'])
            ->setFunctionName('submitMessage')
            ->setFunctionArguments(['_chatId'])
            ->updateHTMLElement('content', 'message', true)
        ;

        $this->addScript($arb);
        $this->addScript('
            async function processSubmitMessage(_chatId) {
                return await submitMessage(_chatId);
            }
        ');
    }

    public function renderChat() {
        $this->template->links = $this->loadFromPresenterCache('links');
        $this->template->user_link = $this->loadFromPresenterCache('user_link');
        $this->template->form = $this->loadFromPresenterCache('form');
    }
}

?>