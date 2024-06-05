<?php

namespace App\Components\PostLister;

use App\Repositories\PostRepository;
use App\Repositories\TopicRepository;
use App\Repositories\UserRepository;

class PostLister {
    private array $posts;
    private array $topics;
    
    private UserRepository $userRepository;
    private TopicRepository $topicRepository;
    private PostRepository $postRepository;

    public function __construct(UserRepository $userRepository, TopicRepository $topicRepository, PostRepository $postRepository) {
        $this->userRepository = $userRepository;
        $this->topicRepository = $topicRepository;
        $this->postRepository = $postRepository;

        $this->posts = [];
        $this->topics = [];
    }

    public function setPosts(array $posts) {
        $this->posts = $posts;
    }

    public function setTopics(array $topics) {
        $this->topics = $topics;
    }

    public function render() {
        return implode('', $this->build());
    }

    private function build() {
        $codeArr = [
            '<script type="text/javascript" src="js/PostListerControl.js"></script>'
        ];

        foreach($this->posts as $post) {
            $liked = $this->postRepository->checkLike($post->getAuthorId(), $post->getId());
            $likeLink = self::createLikeLink($post->getAuthorId(), $post->getId(), $liked);

            $postLink = '<a class="post-title-link" href="?page=UserModule:Posts&action=profile">' . $post->getTitle() . '</a>';

            $code = '<div class="row" id="post-' . $post->getId() . '">';
            $code .= '<div class="col-md">';

            $code .= $postLink;
            $code .= '<hr>';

            $code .= '<p class="post-text">' . $post->getShortenedText(100) . '</p>';
            $code .= '<hr>';

            $code .= '<p class="post-data">Likes: <span id="post-' . $post->getId() . '-likes">' . $post->getLikes() . '</span> <span id="post-' . $post->getId() . '-link">' . $likeLink . '</span> | Author: ' . $this->createUserProfileLink($post->getAuthorId()) . '</p>';

            $code .= '</div></div>';
            $code .= '<br><br>';
            
            $codeArr[] = $code;
        }

        return $codeArr;
    }

    private function createUserProfileLink(int $userId) {
        $user = $this->userRepository->getUserById($userId);

        return '<a class="post-data-link" href="?page=UserModule:Users&action=profile&userId=' . $user->getId() . '">' . $user->getUsername() . '</a>';
    }

    public static function createLikeLink(int $userId, int $postId, bool $liked) {
        if($liked === true) {
            return '<a class="post-like" style="cursor: pointer" onclick="likePost(' . $postId . ', ' . $userId . ', false)">Unlike</a>';
        } else {
            return '<a class="post-like" style="cursor: pointer" onclick="likePost(' . $postId . ', ' . $userId . ', true)">Like</a>';
        }
    }
}

?>