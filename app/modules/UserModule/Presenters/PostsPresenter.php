<?php

namespace App\Modules\UserModule;

use App\Constants\PostTags;
use App\Constants\ReportCategory;
use App\Core\AjaxRequestBuilder;
use App\Core\Caching\CacheNames;
use App\Entities\PostCommentEntity;
use App\Entities\UserEntity;
use App\Exceptions\AException;
use App\Exceptions\AjaxRequestException;
use App\Exceptions\FileUploadException;
use App\Exceptions\GeneralException;
use App\Helpers\BannedWordsHelper;
use App\Helpers\DateTimeFormatHelper;
use App\Managers\EntityManager;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use App\UI\FormBuilder\TextArea;
use App\UI\LinkBuilder;
use Exception;

class PostsPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('PostsPresenter', 'Posts');
    }

    public function startup() {
        parent::startup();
    }

    public function handleProfile() {
        $bwh = new BannedWordsHelper($this->app->contentRegulationRepository, $this->app->topicContentRegulationRepository);

        $postId = $this->httpGet('postId');

        try {
            $post = $this->app->postManager->getPostById($this->getUserId(), $postId);
        } catch(AException $e) {
            $this->flashMessage('Could not open post. Reason: ' . $e->getMessage(), 'error');
            $this->redirect(['page' => 'UserModule:Topics', 'action' => 'followed']);
        }

        if($post->isDeleted() && !$this->app->visibilityAuthorizator->canViewDeletedPost($this->getUserId())) {
            $this->flashMessage('This post does not exist.', 'error');
            $this->redirect(['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $post->getTopicId()]);
        }

        $this->saveToPresenterCache('post', $post);
        
        $postTitle = $bwh->checkText($post->getTitle(), $post->getTopicId());

        if(!empty($bwh->getBannedWordsUsed())) {
            try {
                foreach($bwh->getBannedWordsUsed() as $word) {
                    $this->app->reportManager->reportUserForUsingBannedWord($word, $post->getAuthorId());
                }
            } catch(AException) {}

            $bwh->cleanBannedWordsUsed();
        }

        $this->saveToPresenterCache('postTitle', $postTitle);

        $arb = new AjaxRequestBuilder();

        $arb->setURL(['page' => 'UserModule:Posts', 'action' => 'loadCommentsForPost'])
            ->setMethod('GET')
            ->setHeader(['postId' => '_postId', 'limit' => '_limit', 'offset' => '_offset'])
            ->setFunctionName('loadCommentsForPost')
            ->setFunctionArguments(['_postId', '_limit', '_offset'])
            ->updateHTMLElement('post-comments', 'comments', true)
            ->updateHTMLElement('post-comments-load-more-link', 'loadMoreLink')
        ;

        $this->addScript($arb->build());
        $this->addScript('loadCommentsForPost(\'' . $postId . '\', 10, 0)');

        $arb = new AjaxRequestBuilder();

        $arb->setURL(['page' => 'UserModule:Posts', 'action' => 'likePostComment'])
            ->setMethod('GET')
            ->setHeader(['commentId' => '_commentId', 'toLike' => '_toLike'])
            ->setFunctionName('likePostComment')
            ->setFunctionArguments(['_commentId', '_toLike'])
            ->updateHTMLElementRaw('"#post-comment-" + _commentId + "-likes"', 'likes')
            ->updateHTMLElementRaw('"#post-comment-" + _commentId + "-link"', 'link');
        ;

        $this->addScript($arb->build());

        $arb = new AjaxRequestBuilder();

        $arb->setURL(['page' => 'UserModule:Posts', 'action' => 'createNewCommentForm'])
            ->setMethod('GET')
            ->setHeader(['commentId' => '_commentId', 'postId' => '_postId'])
            ->setFunctionName('createNewCommentForm')
            ->setFunctionArguments(['_commentId', '_postId'])
            ->updateHTMLElementRaw('"#post-comment-" + _commentId + "-comment-form"', 'form')
        ;

        $this->addScript($arb->build());
        
        // new comment form
        $fb = new FormBuilder();

        $fb ->setAction([])
            ->addTextArea('text', 'Comment:', null, true)
            ->addButton('Post comment', 'sendPostComment(\'' . $postId . '\')', 'formSubmit')
        ;

        $fb->updateElement('text', function(TextArea $ta) {
            $ta->setPlaceholder('Your comment...');
            $ta->setId('postCommentText');

            return $ta;
        });

        $this->saveToPresenterCache('form', $fb);

        try {
            $topic = $this->app->topicManager->getTopicById($post->getTopicId(), $this->getUserId());
        } catch (AException $e) {
            $this->flashMessage($e->getMessage(), 'error');
            $this->redirect(['page' => 'UserModule:Home', 'action' => 'dashboard']);
        }

        $topicTitle = $bwh->checkText($topic->getTitle());

        if(!empty($bwh->getBannedWordsUsed())) {
            try {
                $topicOwnerId = $this->app->topicManager->getTopicOwner($topic->getId());

                foreach($bwh->getBannedWordsUsed() as $word) {
                    $this->app->reportManager->reportUserForUsingBannedWord($word, $topicOwnerId);
                }
            } catch(AException) {}

            $bwh->cleanBannedWordsUsed();
        }

        $topicLink = '<a class="post-title-link" href="?page=UserModule:Topics&action=profile&topicId=' . $topic->getId() . '">' . $topicTitle . '</a>';
        $this->saveToPresenterCache('topic', $topicLink);

        $postDescription = $bwh->checkText($post->getText(), $post->getTopicId());

        if(!empty($bwh->getBannedWordsUsed())) {
            try {
                foreach($bwh->getBannedWordsUsed() as $word) {
                    $this->app->reportManager->reportUserForUsingBannedWord($word, $post->getAuthorId());
                }
            } catch(AException) {}

            $bwh->cleanBannedWordsUsed();
        }

        $this->saveToPresenterCache('postDescription', $postDescription);

        // post data
        $likes = $post->getLikes();
        $likeLink = '<a class="post-data-link" href="?page=UserModule:Posts&action=like&postId=' . $postId . '">Like</a>';
        $unlikeLink = '<a class="post-data-link" href="?page=UserModule:Posts&action=unlike&postId=' . $postId . '">Unlike</a>';
        $liked = $this->app->postRepository->checkLike($this->getUserId(), $postId);
        $finalLikeLink = '';

        if(!$post->isDeleted()) {
            $finalLikeLink = ' ' . ($liked ? $unlikeLink : $likeLink);
        }

        try {
            $author = $this->app->userManager->getUserById($post->getAuthorId());
        } catch(AException $e) {
            $this->flashMessage('Could not find user. Reason: ' . $e->getMessage(), 'error');
            $this->redirect(['page' => 'UserModule:Home', 'action' => 'dashboard']);
        }
        $authorLink = $this->app->topicMembershipManager->createUserProfileLinkWithRole($author, $post->getTopicId());

        $reportLink = '';
        if(!$post->isDeleted() && $this->app->actionAuthorizator->canReportPost($this->getUserId(), $topic->getId())) {
            $reportLink = '<a class="post-data-link" href="?page=UserModule:Posts&action=reportForm&postId=' . $postId . '">Report post</a>';
        }

        $deleteLink = '';

        if($this->app->actionAuthorizator->canDeletePost($this->getUserId(), $post->getTopicId()) && !$post->isDeleted()) {
            $deleteLink = '<p class="post-data"><a class="post-data-link" href="?page=UserModule:Posts&action=deletePost&postId=' . $postId . '">Delete post</a></p>';
        } else if($post->isDeleted()) {
            $deleteLink = '<p class="post-data">Post deleted</p>';
        }

        [$tagColor, $tagBgColor] = PostTags::getColorByKey($post->getTag());

        $postedOn = $post->getDateCreated();
        $postedOnText = 'Posted on';
        
        if(strtotime($post->getDateAvailable()) != strtotime($post->getDateCreated())) {
            $postedOn = $post->getDateAvailable();
            $postedOnText = 'Scheduled for';
        }

        $postedOn = DateTimeFormatHelper::formatDateToUserFriendly($postedOn);
        $postedOnAtomic = DateTimeFormatHelper::formatDateToUserFriendly($postedOn, DateTimeFormatHelper::ATOM_FORMAT);

        $postData = '
            <div>
                <div class="row">
                    <div class="col-md-2 col-lg-3">
                        <p class="post-data">Likes: ' . $likes . '' . $finalLikeLink . '</p>
                    </div>

                    <div class="col-md col-lg">
                        <p class="post-data">' . $postedOnText . ': <span title="' . $postedOnAtomic . '">' . $postedOn . '</span></p>
                    </div>

                    <div class="col-md col-lg">
                        <p class="post-data">Author: ' . $authorLink . '</p>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md col-lg">
                        <p class="post-data">Tag: ' . PostTags::createTagText(PostTags::toString($post->getTag()), $tagColor, $tagBgColor, false) . '</p>
                    </div>

                    <div class="col-md col-lg">
                        <p class="post-data">' . $reportLink . '</p>
                    </div>

                    <div class="col-md col-lg">
                        ' . $deleteLink . '
                    </div>
                </div>
            </div>
        ';

        $this->saveToPresenterCache('postData', $postData);

        if($post->isDeleted()) {
            $this->addExternalScript('js/Reducer.js');
            $this->addScript('reducePostProfile()');
        }

        $imagesCode = [];

        $postImages = $this->app->fileUploadRepository->getFilesForPost($postId);
        if(!empty($postImages)) {
            foreach($postImages as $postImage) {
                $imagePath = $this->app->fileUploadManager->createPostImageSourceLink($postImage);
                $imageLink = '<a href="#" onclick="openImage(\'' . $imagePath . '\')"><img src="' . $imagePath . '" class="limited"></a>';

                $imagesCode[] = $imageLink;
            }
        }

        $newImageUploadLink = LinkBuilder::createSimpleLink('Upload image', $this->createURL('uploadImageForm', ['postId' => $postId]), 'post-data-link');

        $newImageUploadSection = '<div class="row">
                <div class="col-md" id="post-upload-image-section">
                    ' . $newImageUploadLink . '
                </div>
            </div>

            <br>';

        if(!$this->app->actionAuthorizator->canUploadFileForPost($this->getUserId(), $post)) {
            $newImageUploadSection = '';
        }

        $postImageCode = '';
        if(!empty($imagesCode)) {
            $tmp = $imagesCode;

            $imagesCode = '';

            $i = 0;
            $x = 0;
            $max = count($tmp);
            while($i < 5) {
                if($x == $max) {
                    break;
                }

                if($i == 4) {
                    $i = 0;
                    $imagesCode .= '<br>';
                } else {
                    $i++;
                }

                $imagesCode .= $tmp[$x];

                $x++;
            }

            $postImageCode = '
                ' . $newImageUploadSection . '

                <div class="row">
                    <div class="col-md-2 col-lg-2"></div>
            
                    <div class="col-md col-lg" id="post-images-section">' . $imagesCode . '</div>
            
                    <div class="col-md-2 col-lg-2"></div>
                </div>

                <br>
            ';
        }

        $this->saveToPresenterCache('postImages', $postImageCode);
    }

    public function renderProfile() {
        $form = $this->loadFromPresenterCache('form');
        $topicLink = $this->loadFromPresenterCache('topic');
        $postData = $this->loadFromPresenterCache('postData');
        $postTitle = $this->loadFromPresenterCache('postTitle');
        $postDescription = $this->loadFromPresenterCache('postDescription');
        $postImages = $this->loadFromPresenterCache('postImages');

        $this->template->post_title = $topicLink . ' | ' . $postTitle;
        $this->template->post_text = $postDescription;
        $this->template->new_comment_form = $form;
        $this->template->post_data = $postData;
        $this->template->post_images = $postImages;
    }

    public function actionAsyncPostComment() {
        $postId = $this->httpGet('postId', true);
        $text = $this->httpGet('text', true);
        $parentCommentId = $this->httpGet('parentCommentId');
        $authorId = $this->getUserId();

        if($text == '') {
            throw new AjaxRequestException('No comment text provided.');
        }

        try {
            $post = $this->app->postManager->getPostById($this->getUserId(), $postId);
        } catch(AException $e) {
            throw new AjaxRequestException('Could not find post.', $e);
        }

        $postLink = LinkBuilder::createSimpleLinkObject($post->getTitle(), ['page' => 'UserModule:Posts', 'action' => 'profile', 'postId' => $postId], 'post-data-link');

        $authorLink = UserEntity::createUserProfileLink($this->getUser(), true);

        $success = false;

        try {
            $this->app->postCommentRepository->beginTransaction();

            $commentId = $this->app->entityManager->generateEntityId(EntityManager::POST_COMMENTS);

            $this->app->postCommentRepository->createNewComment($commentId, $postId, $authorId, $text, $parentCommentId);

            if($post->getAuthorId() != $authorId) {
                $this->app->notificationManager->createNewPostCommentNotification($post->getAuthorId(), $postLink, $authorLink);
            }

            $this->app->postCommentRepository->commit($authorId, __METHOD__);

            $commentEntity = $this->app->postCommentRepository->getCommentById($commentId);

            $success = true;
        } catch (AException $e) {
            $this->app->postCommentRepository->rollback();

            throw new AjaxRequestException('Could not create a new comment.', $e);
        }

        if($success) {
            $bwh = new BannedWordsHelper($this->app->contentRegulationRepository, $this->app->topicContentRegulationRepository);
            $comment = $this->createPostComment($postId, $commentEntity, [], $bwh, [], ($parentCommentId === null));
            $values = ['comment' => $comment];
            if($parentCommentId !== null) {
                $values['parentComment'] = true;
            }
            $commentCount = $this->app->postCommentRepository->getCommentCountForPostId($postId);
            $values['commentCount'] = $commentCount;
            return $values;
        } else {
            throw new AjaxRequestException('Could not post comment.');
        }
    }

    public function handleUploadImageForm() {
        $postId = $this->httpGet('postId');

        $links = [
            LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('profile', ['postId' => $postId]), 'post-data-link')
        ];

        $this->saveToPresenterCache('links', $links);

        $fb = new FormBuilder();
        $fb->setAction($this->createURL('uploadImage', ['postId' => $postId]))
            ->setCanHaveFiles()
            ->addFileInput('image', 'Image:')
            ->addSubmit('Upload')
        ;

        $this->saveToPresenterCache('form', $fb->render());
    }

    public function renderUploadImageForm() {
        $links = $this->loadFromPresenterCache('links');
        $form = $this->loadFromPresenterCache('form');

        $this->template->links = $links;
        $this->template->form = $form;
    }

    public function handleUploadImage() {
        $postId = $this->httpGet('postId');

        try {
            $post = $this->app->postManager->getPostById($this->getUserId(), $postId);

            $this->app->topicRepository->beginTransaction();
            
            if(isset($_FILES['image']['name'])) {
                $this->app->fileUploadManager->uploadPostImage($this->getUserId(), $postId, $post->getTopicId(), $_FILES['image']['name'], $_FILES['image']['tmp_name'], $_FILES['image']);
            } else {
                throw new FileUploadException('No file selected.');
            }

            $this->app->topicRepository->commit($this->getUserId(), __METHOD__);
            $this->flashMessage('Image uploaded.', 'success');
        } catch(Exception $e) {
            $this->app->topicRepository->rollback();
            $this->flashMessage('Image could not be uploaded. Reason: ' . $e->getMessage(), 'error');
        }

        $this->redirect($this->createURL('profile', ['postId' => $postId]));
    }

    public function actionCreateNewCommentForm() {
        $postId = $this->httpGet('postId');
        $parentCommentId = $this->httpGet('commentId');

        $fb = new FormBuilder();

        $fb ->setAction([])
            ->addTextArea('text', 'Comment:', null, true)
            ->addButton('Post comment', 'sendPostComment(\'' . $postId . '\', \'' . $parentCommentId . '\')', 'formSubmit')
        ;

        $fb->updateElement('text', function(TextArea $ta) use ($parentCommentId) {
            $ta->setPlaceholder('Your comment...');
            $ta->setId('postCommentText-' . $parentCommentId);

            return $ta;
        });

        return ['form' => '<div id="post-comment-form">' . $fb->render() . '</div>'];
    }

    public function actionLikePostComment() {
        $commentId = $this->httpGet('commentId');
        $toLike = $this->httpGet('toLike');
        $userId = $this->getUserId();

        $comment = $this->app->postCommentRepository->getCommentById($commentId);

        try {
            $post = $this->app->postManager->getPostById($this->getUserId(), $comment->getPostId());
        } catch(AException $e) {
            throw new AjaxRequestException('Could not find post.', $e);
        }
        $postLink = LinkBuilder::createSimpleLinkObject($post->getTitle(), $this->createURL('profile', ['postId' => $post->getId()]), 'post-data-link');

        $authorLink = LinkBuilder::createSimpleLinkObject($this->getUser()?->getUsername(), ['page' => 'UserModule:Users', 'action' => 'profile', 'userId' => $userId], 'post-data-link');

        $liked = false;
        
        try {
            $this->app->postCommentRepository->beginTransaction();

            if($toLike == 'true') {
                $this->app->postCommentRepository->likeComment($userId, $commentId);
                $liked = true;

                if($userId != $comment->getAuthorId()) {
                    $this->app->notificationManager->createNewCommentLikeNotification($comment->getAuthorId(), $postLink, $authorLink);
                }
            } else {
                $this->app->postCommentRepository->unlikeComment($userId, $commentId);
            }

            $this->app->postCommentRepository->commit($userId, __METHOD__);
        } catch(AException $e) {
            $this->app->postCommentRepository->rollback();
            
            throw new AjaxRequestException('Could not ' . $liked ? 'like' : 'unlike' . ' post.', $e);
            
            $liked = false;
        }
            
        $likes = $this->app->postCommentRepository->getLikes($commentId);
        
        $link = '<a class="post-comment-link" style="cursor: pointer" onclick="likePostComment(\'' . $commentId .'\', ' . ($liked ? 'false' : 'true') . ')">' . ($liked ? 'Unlike' : 'Like') . '</a>';

        return ['link' => $link, 'likes' => $likes];
    }

    public function actionLoadCommentsForPost() {
        $postId = $this->httpGet('postId');
        $limit = $this->httpGet('limit');
        $offset = $this->httpGet('offset');

        try {
            $post = $this->app->postManager->getPostById($this->getUserId(), $postId);
        } catch(AException $e) {
            throw new AjaxRequestException('Could not find post.', $e);
        }

        $comments = $this->app->postCommentRepository->getLatestCommentsForPostId($postId, $limit, $offset, !$post->isDeleted());
        $commentCount = $this->app->postCommentRepository->getCommentCountForPostId($postId, !$post->isDeleted());

        $allComments = $this->app->postCommentRepository->getCommentsForPostId($postId);

        $commentIds = [];
        foreach($allComments as $comment) {
            $commentIds[] = $comment->getId();
        }

        $likedComments = $this->app->postCommentRepository->getLikedCommentsForUser($this->getUserId(), $commentIds);

        $childrenComments = $this->app->postCommentRepository->getCommentsThatHaveAParent($postId, true);

        $code = [];

        if(empty($comments)) {
            return ['comments' => 'No comments found', 'loadMoreLink' => ''];
        }

        $bwh = new BannedWordsHelper($this->app->contentRegulationRepository, $this->app->topicContentRegulationRepository);

        foreach($comments as $comment) {
            $code[] = $this->createPostComment($postId, $comment, $likedComments, $bwh, $childrenComments);
        }

        if(($offset + $limit) >= $commentCount) {
            $loadMoreLink = '';
        } else {
            $loadMoreLink = '<br><button type="button" id="formSubmit" onclick="loadCommentsForPost(\'' . $postId . '\', ' . $limit . ', ' . ($offset + $limit) . ')">Load more</button>';
        }

        $c = '';
        if($offset > 0) {
            $c = '<br>';
        }

        return ['comments' => $c . implode('<br>', $code), 'loadMoreLink' => $loadMoreLink];
    }

    private function createPostComment(string $postId, PostCommentEntity $comment, array $likedComments, BannedWordsHelper $bwh, array $childComments, bool $parent = true) {
        $post = $this->app->postManager->getPostById($this->getUserId(), $postId);

        $author = $this->app->userManager->getUserById($comment->getAuthorId());
        $userProfileLink = $this->app->topicMembershipManager->createUserProfileLinkWithRole($author, $post->getTopicId(), '', 'post-comment-link');

        $liked = in_array($comment->getId(), $likedComments);
        if(!$post->isDeleted()) {
            $likeLink = '<a class="post-comment-link" style="cursor: pointer" onclick="likePostComment(\'' . $comment->getId() .'\', ' . ($liked ? 'false' : 'true') . ')">' . ($liked ? 'Unlike' : 'Like') . '</a>';
        } else {
            $likeLink = '';
        }

        $childCommentsCode = [];

        if(!empty($childComments)) {
            foreach($childComments as $cc) {
                if($cc->getParentCommentId() == $comment->getId()) {
                    $childCommentsCode[] = $this->createPostComment($postId, $cc, $likedComments, $bwh, $childComments, false);
                }
            }
        }

        if(!$post->isDeleted() && $this->app->actionAuthorizator->canReportPost($this->getUserId(), $post->getTopicId())) {
            $reportForm = '<a class="post-comment-link" href="?page=UserModule:Posts&action=reportComment&commentId=' . $comment->getId() . '">Report</a>';
        } else {
            $reportForm = '';
        }
        $deleteLink = '';
        
        if($this->app->actionAuthorizator->canDeleteComment($this->getUserId(), $post->getTopicId()) && !$post->isDeleted()) {
            $deleteLink = ' | <a class="post-comment-link" href="?page=UserModule:Posts&action=deleteComment&commentId=' . $comment->getId() . '&postId=' . $postId . '">Delete</a>';
        }

        $text = $bwh->checkText($comment->getText(), $post->getTopicId());

        if(!empty($bwh->getBannedWordsUsed())) {
            try {
                foreach($bwh->getBannedWordsUsed() as $word) {
                    $this->app->reportManager->reportUserForUsingBannedWord($word, $comment->getAuthorId());
                }
            } catch(AException) {}

            $bwh->cleanBannedWordsUsed();
        }

        $matches = [];
        preg_match_all("/[@]\w*/m", $text, $matches);

        $matches = $matches[0];

        $users = [];
        foreach($matches as $match) {
            $username = substr($match, 1);
            $user = $this->app->userRepository->getUserByUsername($username);
            if($user === null) {
                $users[$match] = '@' . $username;
            } else {
                $link = $this->app->topicMembershipManager->createUserProfileLinkWithRole($user, $post->getTopicId(), '@');
                
                $users[$match] = $link;
            }
        }

        foreach($users as $k => $v) {
            $text = str_replace($k, $v, $text);
        }

        $pattern = "/\[(.*?),\s*(https?:\/\/[^\]]+)\]/";
        $replacement = '<a class="post-text-link" href="$2" target="_blank">$1</a>';

        $text = preg_replace($pattern, $replacement, $text);

        $dateCreated = DateTimeFormatHelper::formatDateToUserFriendly($comment->getDateCreated());
        $dateCreatedAtomic = DateTimeFormatHelper::formatDateToUserFriendly($comment->getDateCreated(), DateTimeFormatHelper::ATOM_FORMAT);

        $code = '
            <div class="row' . ($parent ? '' : ' post-comment-border') . '" id="post-comment-id-' . $comment->getId() . '">
                ' . ($parent ? '' : '<div class="col-md-1"></div>') . '
                <div class="col-md">
                    <div>
                        <p class="post-comment-text">' . $text . '</p>
                        <p class="post-comment-data">Likes: <span id="post-comment-' . $comment->getId() . '-likes">' . $comment->getLikes() . '</span> <span id="post-comment-' . $comment->getId() . '-link">' . $likeLink . '</span>
                                            | Author: ' . $userProfileLink . ' | Date: <span title="' . $dateCreatedAtomic . '">' . $dateCreated . '</span>
                        </p>
                        <p class="post-comment-data">
                            ' . $reportForm . $deleteLink . '
                        </p>
                        ' . ($post->isDeleted() ? '' : '<a class="post-comment-link" id="post-comment-' . $comment->getId() . '-add-comment-link" style="cursor: pointer" onclick="createNewCommentForm(\'' . $comment->getId() . '\', \'' . $postId . '\')">Add comment</a>') . '
                    </div>
                    <div class="row">
                        <div class="col-md-2"></div>

                        <div class="col-md" id="form">
                            <div id="post-comment-' . $comment->getId() . '-comment-form"></div>
                        </div>
                        
                        <div class="col-md-2"></div>
                    </div>
                    <div id="post-comment-child-comments-' . $comment->getId() . '">
                    ' . implode('', $childCommentsCode) .  '
                    </div>
                    ' . ($parent ? '' : '<div class="col-md-1"></div>') . '
                </div>
            </div>
        ';

        return $code;
    }

    public function handleNewComment(FormResponse $fr) {
        $text = $fr->text;
        $postId = $this->httpGet('postId');
        $authorId = $this->getUserId();
        $parentCommentId = $this->httpGet('parentCommentId');

        try {
            $post = $this->app->postManager->getPostById($this->getUserId(), $postId);
        } catch(AException $e) {
            $this->flashMessage('Could not find post. Reason: ' . $e->getMessage(), 'error');
            $this->redirect(['page' => 'UserModule:Posts', 'action' => 'profile', 'postId' => $postId]);
        }
        $postLink = LinkBuilder::createSimpleLinkObject($post->getTitle(), ['page' => 'UserModule:Posts', 'action' => 'profile', 'postId' => $postId], 'post-data-link');

        $authorLink = UserEntity::createUserProfileLink($this->getUser(), true);
        
        try {
            $this->app->postCommentRepository->beginTransaction();

            $commentId = $this->app->entityManager->generateEntityId(EntityManager::POST_COMMENTS);

            $this->app->postCommentRepository->createNewComment($commentId, $postId, $authorId, $text, $parentCommentId);

            if($post->getAuthorId() != $authorId) {
                $this->app->notificationManager->createNewPostCommentNotification($post->getAuthorId(), $postLink, $authorLink);
            }

            $this->app->postCommentRepository->commit($authorId, __METHOD__);

            $this->flashMessage('Comment posted.', 'success');
        } catch (AException $e) {
            $this->app->postCommentRepository->rollback();

            $this->flashMessage('Comment could not be created. Reason: ' . $e->getMessage(), 'error');
        }
        
        $this->redirect(['page' => 'UserModule:Posts', 'action' => 'profile', 'postId' => $postId]);
    }

    public function handleReportForm(?FormResponse $fr = null) {
        $postId = $this->httpGet('postId');
        
        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $category = $fr->category;
            $description = $fr->description;
            $userId = $this->getUserId();

            try {
                $this->app->reportRepository->beginTransaction();

                $this->app->reportRepository->createPostReport($userId, $postId, $category, $description);

                $this->app->reportRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Post reported.', 'success');
            } catch(AException $e) {
                $this->app->reportRepository->rollback();

                $this->flashMessage('Post could not be reported. Reason: ' . $e->getMessage(), 'error');
            }

            $this->redirect(['page' => 'UserModule:Posts', 'action' => 'profile', 'postId' => $postId]);
        } else {
            try {
                $post = $this->app->postManager->getPostById($this->getUserId(), $postId);
            } catch(AException $e) {
                $this->flashMessage('Could not find post. Reason: ' . $e->getMessage(), 'error');
                $this->redirect(['page' => 'UserModule:Posts', 'action' => 'profile', 'postId' => $postId]);
            }
            $this->saveToPresenterCache('post', $post);

            $categories = ReportCategory::getArray();
            $categoryArray = [];
            foreach($categories as $k => $v) {
                $categoryArray[] = [
                    'value' => $k,
                    'text' => $v
                ];
            }

            $fb = new FormBuilder();
            $fb ->setAction(['page' => 'UserModule:Posts', 'action' => 'reportForm', 'isSubmit' => '1', 'postId' => $postId])
                ->addSelect('category', 'Category:', $categoryArray, true)
                ->addTextArea('description', 'Additional notes:', null, true)
                ->addSubmit('Send')
                ;

            $this->saveToPresenterCache('form', $fb);

            $links = [
                LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('profile', ['postId' => $postId]), 'post-data-link')
            ];

            $this->saveToPresenterCache('links', $links);
        }
    }

    public function renderReportForm() {
        $post = $this->loadFromPresenterCache('post');
        $form = $this->loadFromPresenterCache('form');
        $links = $this->loadFromPresenterCache('links');

        $this->template->post_title = $post->getTitle();
        $this->template->form = $form;
        $this->template->links = $links;
    }

    public function handleReportComment(?FormResponse $fr = null) {
        $commentId = $this->httpGet('commentId');
        $comment = $this->app->postCommentRepository->getCommentById($commentId);

        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $category = $fr->category;
            $description = $fr->description;
            $userId = $this->getUserId();

            try {
                $this->app->reportRepository->beginTransaction();

                $this->app->reportRepository->createCommentReport($userId, $commentId, $category, $description);

                $this->app->reportRepository->commit($userId, __METHOD__);

                $this->flashMessage('Comment reported.', 'success');
            } catch(AException $e) {
                $this->app->reportRepository->rollback();

                $this->flashMessage('Comment could not be reported. Reason: ' . $e->getMessage(), __METHOD__);
            }

            $this->redirect(['page' => 'UserModule:Posts', 'action' => 'profile', 'postId' => $comment->getPostId()]);
        } else {
            $categories = ReportCategory::getArray();
            $categoryArray = [];
            foreach($categories as $k => $v) {
                $categoryArray[] = [
                    'value' => $k,
                    'text' => $v
                ];
            }

            $fb = new FormBuilder();
            $fb ->setAction(['page' => 'UserModule:Posts', 'action' => 'reportComment', 'isSubmit' => '1', 'commentId' => $commentId])
                ->addSelect('category', 'Category:', $categoryArray, true)
                ->addTextArea('description', 'Additional notes:', null, true)
                ->addSubmit('Send')
                ;

            $this->saveToPresenterCache('form', $fb);

            $links = [
                LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('profile', ['postId' => $comment->getPostId()]), 'post-data-link')
            ];

            $this->saveToPresenterCache('links', $links);
        }
    }

    public function renderReportComment() {
        $form = $this->loadFromPresenterCache('form');
        $links = $this->loadFromPresenterCache('links');

        $this->template->form = $form;
        $this->template->links = $links;
    }

    public function handleDeleteComment(?FormResponse $fr = null) {
        $commentId = $this->httpGet('commentId');
        $postId = $this->httpGet('postId');
        
        if($this->httpGet('isSubmit') == '1') {
            try {
                $post = $this->app->postManager->getPostById($this->getUserId(), $postId);
            } catch(AException $e) {
                $this->flashMessage('Could not find post. Reason: ' . $e->getMessage(), 'error');
                $this->redirect(['action' => 'profile', 'postId' => $postId]);
            }
            $comment = $this->app->postCommentRepository->getCommentById($commentId);
            $postLink = LinkBuilder::createSimpleLinkObject($post->getTitle(), $this->createURL('profile', ['postId' => $postId]), 'post-data-link');
            $userLink = UserEntity::createUserProfileLink($this->getUser(), true);
            
            try {
                $this->app->postRepository->beginTransaction();
                
                $this->app->contentManager->deleteComment($commentId);

                $this->app->notificationManager->createNewCommentDeletedNotification($comment->getAuthorId(), $postLink, $userLink);

                $this->app->postRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Comment #' . $commentId . ' has been deleted.', 'success');
            } catch(Exception $e) {
                $this->app->postRepository->rollback();

                $this->flashMessage('Comment #' . $commentId . ' could not be deleted. Reason: ' . $e->getMessage(), 'error');
            }
            
            $this->redirect(['action' => 'profile', 'postId' => $postId]);
        } else {
            $fb = new FormBuilder();
            
            $fb ->setAction(['page' => 'UserModule:Posts', 'action' => 'deleteComment', 'isSubmit' => '1', 'commentId' => $commentId, 'postId' => $postId])
                ->addSubmit('Delete comment')
                ->addButton('Go back', 'location.href = \'?page=UserModule:Posts&action=profile&postId=' . $postId . '\';', 'formSubmit')
            ;

            $this->saveToPresenterCache('form', $fb);
        }
    }

    public function renderDeleteComment() {
        $form = $this->loadFromPresenterCache('form');

        $this->template->form = $form;
    }

    public function handleDeletePost(?FormResponse $fr = null) {
        $postId = $this->httpGet('postId');

        if($this->httpGet('isSubmit') == '1') {
            try {
                $post = $this->app->postManager->getPostById($this->getUserId(), $postId);
            } catch(AException $e) {
                $this->flashMessage('Could not find post. Reason: ' . $e->getMessage(), 'error');
                $this->redirect($this->createURL('profile', ['postId' => $postId]));
            }
            $postLink = LinkBuilder::createSimpleLinkObject($post->getTitle(), $this->createURL('profile', ['postId' => $postId]), 'post-data-link');
            $userLink = UserEntity::createUserProfileLink($this->getUser(), true);

            try {
                if($fr->postTitle != $post->getTitle()) {
                    throw new GeneralException('Post titles do not match.');
                }

                $this->app->userAuth->authUser($fr->getHashedPassword($fr->userPassword));

                $this->app->postRepository->beginTransaction();

                $this->app->contentManager->deletePost($postId);

                $this->app->notificationManager->createNewPostDeletedNotification($post->getAuthorId(), $postLink, $userLink);

                $this->app->postRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Post #' . $postId . ' has been deleted.', 'success');
            } catch(Exception $e) {
                $this->app->postRepository->rollback();

                $this->flashMessage('Post #' . $postId . ' could not be deleted. Reason: ' . $e->getMessage(), 'error');
            }

            $this->redirect(['action' => 'profile', 'postId' => $postId]);
        } else {
            $fb = new FormBuilder();
            
            $fb ->setAction(['page' => 'UserModule:Posts', 'action' => 'deletePost', 'isSubmit' => '1', 'postId' => $postId])
                ->addTextInput('postTitle', 'Post title:', null, true)
                ->addPassword('userPassword', 'Your password:', null, true)
                ->addSubmit('Delete post')
                ->addButton('&larr; Go back', 'location.href = \'?page=UserModule:Posts&action=profile&postId=' . $postId . '\';', 'formSubmit')
            ;

            $this->saveToPresenterCache('form', $fb);
        }
    }

    public function renderDeletePost() {
        $form = $this->loadFromPresenterCache('form');

        $this->template->form = $form;
    }

    public function handleLike() {
        $postId = $this->httpGet('postId', true);
        try {
            $post = $this->app->postManager->getPostById($this->getUserId(), $postId);
        } catch(AException $e) {
            $this->flashMessage('Could not find post. Reason: ' . $e->getMessage(), 'error');
            $this->redirect($this->createURL('profile', ['postId' => $postId]));
        }

        $postLink = LinkBuilder::createSimpleLinkObject($post->getTitle(), ['page' => 'UserModule:Posts', 'action' => 'profile', 'postId' => $postId], 'post-data-link');
        
        try {
            $this->app->postRepository->beginTransaction();
            
            $this->app->postRepository->likePost($this->getUserId(), $postId);
            $this->app->postRepository->updatePost($postId, ['likes' => $post->getLikes() + 1]);

            if($this->getUserId() != $post->getAuthorId()) {
                $this->app->notificationManager->createNewPostLikeNotification($post->getAuthorId(), $postLink, UserEntity::createUserProfileLink($this->getUser(), true));
            }

            $cache = $this->cacheFactory->getCache(CacheNames::POSTS);
            $cache->invalidate();

            $this->app->postRepository->commit($this->getUserId(), __METHOD__);
        } catch(AException $e) {
            $this->app->postRepository->rollback();
            $this->flashMessage('Could not like post #' . $postId . '. Reason: ' . $e->getMessage(), 'error');
        }

        $this->redirect(['page' => 'UserModule:Posts', 'action' => 'profile', 'postId' => $postId]);
    }

    public function handleUnlike() {
        $postId = $this->httpGet('postId', true);
        try {
            $post = $this->app->postManager->getPostById($this->getUserId(), $postId);
        } catch(AException $e) {
            $this->flashMessage('Could not find post. Reason: ' . $e->getMessage(), 'error');
            $this->redirect($this->createURL('profile', ['postId' => $postId]));
        }

        try {
            $this->app->postRepository->beginTransaction();

            $this->app->postRepository->unlikePost($this->getUserId(), $postId);
            $this->app->postRepository->updatePost($postId, ['likes' => $post->getLikes() - 1]);

            $cache = $this->cacheFactory->getCache(CacheNames::POSTS);
            $cache->invalidate();
            
            $this->app->postRepository->commit($this->getUserId(), __METHOD__);
        } catch(AException $e) {
            $this->app->postRepository->rollback();

            $this->flashMessage('Could not unlike post #' . $postId . '. Reason: ' . $e->getMessage(), 'error');
        }

        $this->redirect(['page' => 'UserModule:Posts', 'action' => 'profile', 'postId' => $postId]);
    }
}

?>