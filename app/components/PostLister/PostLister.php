<?php

namespace App\Components\PostLister;

use App\Entities\UserEntity;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Helpers\BannedWordsHelper;
use App\Managers\FileUploadManager;
use App\Managers\ReportManager;
use App\Managers\TopicManager;
use App\Repositories\ContentRegulationRepository;
use App\Repositories\FileUploadRepository;
use App\Repositories\PostRepository;
use App\Repositories\TopicRepository;
use App\Repositories\UserRepository;

class PostLister {
    private array $posts;
    private array $topics;
    private bool $topicLinkHidden;
    private ?UserEntity $currentUser;
    private int $limit;
    
    private UserRepository $userRepository;
    private TopicRepository $topicRepository;
    private PostRepository $postRepository;
    private ?ContentRegulationRepository $crr;
    private FileUploadRepository $fur;
    private FileUploadManager $fum;
    private ReportManager $reportManager;
    private TopicManager $topicManager;

    public function __construct(UserRepository $userRepository, TopicRepository $topicRepository, PostRepository $postRepository, ?ContentRegulationRepository $crr, FileUploadRepository $fur, FileUploadManager $fum, ReportManager $reportManager, TopicManager $topicManager) {
        $this->userRepository = $userRepository;
        $this->topicRepository = $topicRepository;
        $this->postRepository = $postRepository;
        $this->crr = $crr;
        $this->fur = $fur;
        $this->fum = $fum;
        $this->reportManager = $reportManager;
        $this->topicManager = $topicManager;

        $this->posts = [];
        $this->topics = [];

        $this->topicLinkHidden = false;

        $this->currentUser = null;
        $this->limit = 10;
    }

    public function setLimit(int $limit) {
        $this->limit = $limit;
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
            shuffle($this->posts);
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

            $postIds = [];
            foreach($this->posts as $post) {
                $postIds[] = $post->getId();
            }

            $postImages = $this->fur->getBulkFilesForPost($postIds);

            $getImagesForPost = function (string $postId) use ($postImages) {
                $images = [];

                foreach($postImages as $pi) {
                    if($pi->getPostId() == $postId) {
                        $images[] = $pi;
                    }
                }

                return $images;
            };

            $x = 0;
            foreach($this->posts as $post) {
                if($x >= $this->limit) {
                    break;
                }

                $liked = in_array($post->getId(), $likedArray);
                $likeLink = self::createLikeLink($post->getId(), $liked);
                
                if(!empty($this->topics)) {
                    $topics = [];
    
                    foreach($this->topics as $topic) {
                        if($topic === null) {
                            continue;
                        }
                        $topics[$topic->getId()] = $topic;
                    }

                    $title = $post->getTitle();
                    if($bwh !== null) {
                        $title = $bwh->checkText($title);
                    }

                    $text = $post->getShortenedText(100);
                    if($bwh !== null) {
                        $text = $bwh->checkText($text);

                        if(!empty($bwh->getBannedWordsUsed())) {
                            try {
                                foreach($bwh->getBannedWordsUsed() as $word) {
                                    $this->reportManager->reportUserForUsingBannedWord($word, $post->getAuthorId());
                                }
                            } catch(AException) {}
            
                            $bwh->cleanBannedWordsUsed();
                        }
                    }

                    $topicTitle = $topics[$post->getTopicId()]->getTitle();
                    if($bwh !== null) {
                        $topicTitle = $bwh->checkText($topicTitle);

                        if(!empty($bwh->getBannedWordsUsed())) {
                            try {
                                $topicOwnerId = $this->topicManager->getTopicOwner($topic->getId());
                
                                foreach($bwh->getBannedWordsUsed() as $word) {
                                    $this->reportManager->reportUserForUsingBannedWord($word, $topicOwnerId);
                                }
                            } catch(AException) {}
                
                            $bwh->cleanBannedWordsUsed();
                        }
                    }
    
                    $postLink = '<a class="post-title-link" href="?page=UserModule:Posts&action=profile&postId=' . $post->getId() . '">' . $title . '</a>';
                    $topicLink = '<a class="post-title-link-smaller" href="?page=UserModule:Topics&action=profile&topicId=' . $post->getTopicId() . '">' . $topicTitle . '</a>';
    
                    $code = '<div class="row" id="post-id-' . $post->getId() . '">';
                    $code .= '<div class="col-md">';
                    
                    $code .= '<div class="row">';
                    $code .= '<div class="col-md">';
                    $code .= '<p class="post-title">' . (!$this->topicLinkHidden ? $topicLink . ' | ' : '') . $postLink . '</p>';
                    $code .= '</div>';
                    $code .= '</div>';
                    $code .= '<hr>';

                    $images = $getImagesForPost($post->getId());

                    if(!empty($images)) {
                        $imageJson = [];
                        foreach($images as $image) {
                            $imageJson[] = $this->fum->createPostImageSourceLink($image);
                        }
                        $imageJson = json_encode($imageJson);

                        $path = $this->fum->createPostImageSourceLink($images[0]);

                        $imageCode = '<div id="post-' . $post->getId() . '-image-preview-json" style="position: relative; visibility: hidden; width: 0; height: 0">' . $imageJson . '</div><div class="row">';

                        // left button
                        if(count($images) > 1) {
                            $imageCode .= '<div class="col-md-1"><span id="post-' . $post->getId() . '-image-preview-left-button"></span></div>';
                        }

                        // image
                        $imageCode .= '<div class="col-md"><span id="post-' . $post->getId() . '-image-preview"><a href="#post-' . $post->getId() . '" id="post-' . $post->getId() . '-image-preview-source" onclick="openImagePostLister(\'' . $path . '\', ' . $post->getId() . ')"><img src="' . $path . '" class="limited"></a></span></div>';
                        
                        // right button
                        if(count($images) > 1) {
                            $imageCode .= '<div class="col-md-1"><span id="post-' . $post->getId() . '-image-preview-right-button"><button class="post-image-browser-link" type="button" onclick="changeImage(' . $post->getId() . ', 1, ' . (count($images) - 1) . ')">&rarr;</button></span></div>';
                        }

                        $imageCode .= '</div>';

                        $code .= $imageCode;
                    }

                    $code .= '<div class="row"><div class="col-md">';
    
                    $code .= '<p class="post-text">' . $text . '</p>';
                    $code .= '</div></div>';
                    $code .= '<hr>';
    
                    $code .= '<div class="row"><div class="col-md">';
                    $code .= '<p class="post-data">Likes: <span id="post-' . $post->getId() . '-likes">' . $post->getLikes() . '</span> <span id="post-' . $post->getId() . '-link">' . $likeLink . '</span> | Author: ' . $this->createUserProfileLink($post->getAuthorId()) . '</p>';
                    $code .= '</div></div>';
                    $code .= '</div></div>';
                    $code .= '<br>';
    
                    $codeArr[] = $code;
                }

                $x++;
            }
        } else {
            $codeArr[] = '<p class="post-text">No posts found!</p>';
        }

        $codeArr[] = '</div>';

        return $codeArr;
    }

    private function createUserProfileLink(string $userId) {
        $user = $this->userRepository->getUserById($userId);

        return '<a class="post-data-link" href="?page=UserModule:Users&action=profile&userId=' . $user->getId() . '">' . $user->getUsername() . '</a>';
    }

    public static function createLikeLink(string $postId, bool $liked) {
        if($liked === true) {
            return '<a class="post-like" style="cursor: pointer" onclick="likePost(\'' . $postId . '\', false)">Unlike</a>';
        } else {
            return '<a class="post-like" style="cursor: pointer" onclick="likePost(\'' . $postId . '\', true)">Like</a>';
        }
    }
}

?>