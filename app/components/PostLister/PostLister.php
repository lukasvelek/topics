<?php

namespace App\Components\PostLister;

use App\Entities\UserEntity;
use App\Exceptions\GeneralException;
use App\Helpers\BannedWordsHelper;
use App\Repositories\ContentRegulationRepository;
use App\Repositories\PostRepository;
use App\Repositories\TopicRepository;
use App\Repositories\UserRepository;

class PostLister {
    private array $posts;
    private array $topics;
    private bool $topicLinkHidden;
    private ?UserEntity $currentUser;
    
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

        $this->currentUser = null;
    }

    public function setCurrentUser(UserEntity $user) {
        $this->currentUser = $user;
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
        if($this->currentUser === null) {
            throw new GeneralException('Current user must be set!');
        }

        $codeArr = [
            '<div id="post-lister">'
        ];

        if(!empty($this->posts)) {
            $bwh = null;
            if($this->crr !== null) {
                $bwh = new BannedWordsHelper($this->crr);
            }

            $postIds = [];
            foreach($this->posts as $post) {
                $postIds[] = $post->getId();
            }

            $likedArray = $this->postRepository->bulkCheckLikes($this->currentUser->getId(), $postIds);

            foreach($this->posts as $post) {
                $liked = in_array($post->getId(), $likedArray);
                $likeLink = self::createLikeLink($post->getId(), $liked);
                
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

                    if(!$this->topicLinkHidden) {
                        $code .= '<div class="row">';

                        $code .= '<div class="col-md">';
                        $code .= '<p class="post-title">' . $topicLink . '</p>';
                        $code .= '</div>';
                        
                        $code .= '<div class="col-md">';
                        $code .= '<p class="post-title">' . $postLink . '</p>';
                        $code .= '</div>';

                        $code .= '</div>';
                    }

                    $code .= '<div class="row">';
                    $code .= '<div class="col-md">';
                    $code .= '</div>';

                    $code .= '<div class="col-md">';
                    $code .= '</div>';
    
                    $code .= '<p class="post-title">' . (!$this->topicLinkHidden ? $topicLink . ' | ' : '') . $postLink . '</p>';

                    $code .= '</div>';

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

    public static function createLikeLink(int $postId, bool $liked) {
        if($liked === true) {
            return '<a class="post-like" style="cursor: pointer" onclick="likePost(' . $postId . ', false)">Unlike</a>';
        } else {
            return '<a class="post-like" style="cursor: pointer" onclick="likePost(' . $postId . ', true)">Like</a>';
        }
    }
}

?>