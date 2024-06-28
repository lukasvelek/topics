<?php

namespace App\Components\PostLister;

use App\Helpers\BannedWordsHelper;
use App\Repositories\ContentRegulationRepository;
use App\Repositories\PostRepository;
use App\Repositories\TopicRepository;
use App\Repositories\UserRepository;

class PostLister {
    private array $posts;
    private array $topics;

    private bool $topicLinkHidden;
    
    private UserRepository $userRepository;
    private TopicRepository $topicRepository;
    private PostRepository $postRepository;
    private ?ContentRegulationRepository $crr;

    public function __construct(UserRepository $userRepository, TopicRepository $topicRepository, PostRepository $postRepository, ?ContentRegulationRepository $crr) {
        $this->userRepository = $userRepository;
        $this->topicRepository = $topicRepository;
        $this->postRepository = $postRepository;
        $this->crr = $crr;

        $this->posts = [];
        $this->topics = [];

        $this->topicLinkHidden = false;
    }

    public function setPosts(array $posts) {
        $this->posts = $posts;
    }

    public function setTopics(array $topics) {
        $this->topics = $topics;
    }

    public function setTopicLinkHidden(bool $hidden = true) {
        $this->topicLinkHidden = $hidden;
    }

    public function shufflePosts() {
        if(empty($this->posts)) {
            return;
        }

        $loops = 3;

        for($j = 0; $j < $loops; $j++) {
            $tmp = [];
            $used = [];

            $i = 0;
            while($i < count($this->posts)) {
                $r = rand(0, count($this->posts) - 1);

                
                if(!in_array($this->posts[$r]->getId(), $used)) {
                    $tmp[] = $this->posts[$r];
                    $used[] = $this->posts[$r]->getId();
                    $i++;
                }
            }

            $this->posts = $tmp;
        }
    }

    public function render() {
        return implode('', $this->build());
    }

    private function build() {
        $codeArr = [
            '<script type="text/javascript" src="js/PostListerControl.js"></script>',
            '<div id="post-lister">'
        ];

        if(!empty($this->posts)) {
            $bwh = null;
            if($this->crr !== null) {
                $bwh = new BannedWordsHelper($this->crr);
            }

            foreach($this->posts as $post) {
                $liked = $this->postRepository->checkLike($post->getAuthorId(), $post->getId());
                $likeLink = self::createLikeLink($post->getAuthorId(), $post->getId(), $liked);
                
                if(!empty($this->topics)) {
                    $topics = [];
    
                    foreach($this->topics as $topic) {
                        $topics[$topic->getId()] = $topic;
                    }

                    $title = $post->getTitle();
                    if($bwh !== null) {
                        $title = $bwh->checkText($title);
                    }

                    $text = $post->getShortenedText(100);
                    if($bwh !== null) {
                        $text = $bwh->checkText($text);
                    }

                    $topicTitle = $topics[$post->getTopicId()]->getTitle();
                    if($bwh !== null) {
                        $topicTitle = $bwh->checkText($topicTitle);
                    }
    
                    $postLink = '<a class="post-title-link" href="?page=UserModule:Posts&action=profile&postId=' . $post->getId() . '">' . $title . '</a>';
                    $topicLink = '<a class="post-title-link-smaller" href="?page=UserModule:Topics&action=profile&topicId=' . $post->getTopicId() . '">' . $topicTitle . '</a>';
    
                    $code = '<div class="row" id="post-' . $post->getId() . '">';
                    $code .= '<div class="col-md">';
    
                    $code .= '<p class="post-title">' . (!$this->topicLinkHidden ? $topicLink . ' | ' : '') . $postLink . '</p>';
                    $code .= '<hr>';
    
                    $code .= '<p class="post-text">' . $text . '</p>';
                    $code .= '<hr>';
    
                    $code .= '<p class="post-data">Likes: <span id="post-' . $post->getId() . '-likes">' . $post->getLikes() . '</span> <span id="post-' . $post->getId() . '-link">' . $likeLink . '</span> | Author: ' . $this->createUserProfileLink($post->getAuthorId()) . '</p>';
                    $code .= '</div></div>';
                    $code .= '<br><br>';
    
                    $codeArr[] = $code;
                }
            }
        } else {
            $codeArr[] = '<p class="post-text">No posts found!</p>';
        }

        $codeArr[] = '</div>';

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