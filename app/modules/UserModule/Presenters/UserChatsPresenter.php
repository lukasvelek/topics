<?php

namespace App\Modules\UserModule;

use App\Core\AjaxRequestBuilder;

class UserChatsPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('UserChatsPresenter', 'Chats');
    }

    public function startup() {
        parent::startup();
    }

    public function handleList() {
        $offset = $this->httpGet('offset') ?? '0';

        $links = [];

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

        $chats = $this->app->chatManager->getChatsForUser($this->getUserId(), $limit, $offset);

        $code = '<div>';

        foreach($chats as $chat) {
            if($this->getUserId() == $chat->getUser1Id()) {
                $username = $this->app->userRepository->getUserById($chat->getUser2Id())->getUsername();
            } else {
                $username = $this->app->userRepository->getUserById($chat->getUser1Id())->getUsername();
            }

            $code .= '<a href="' . $this->createFullURLString('UserModule:UserChats', 'chat', ['chatId' => $chat->getChatId()]) . '"><div id="chat-' . $chat->getChatId() . '">';
            $code .= '<span>' . $username . '</span>';
            $code .= '</div></a>';
        }

        $code .= '</div>';

        return ['list' => $code];
    }
}

?>