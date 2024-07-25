<?php

namespace App\Modules\UserModule;

use App\Constants\PostTags;
use App\Constants\ReportCategory;
use App\Core\AjaxRequestBuilder;
use App\Core\CacheManager;
use App\Entities\PostCommentEntity;
use App\Entities\PostEntity;
use App\Entities\UserEntity;
use App\Exceptions\AException;
use App\Exceptions\FileUploadException;
use App\Helpers\BannedWordsHelper;
use App\Helpers\DateTimeFormatHelper;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use App\UI\LinkBuilder;
use Exception;

class PostsPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('PostsPresenter', 'Posts');
    }

    public function handleProfile() {
        global $app;

        $bwh = new BannedWordsHelper($app->contentRegulationRepository);

        $postId = $this->httpGet('postId');
        $post = $app->postRepository->getPostById($postId);

        if($post->isDeleted() && !$app->visibilityAuthorizator->canViewDeletedPost($app->currentUser->getId())) {
            $this->flashMessage('This post does not exist.', 'error');
            $this->redirect(['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $post->getTopicId()]);
        }

        $this->saveToPresenterCache('post', $post);
        
        $postTitle = $bwh->checkText($post->getTitle());
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
        $this->addScript('loadCommentsForPost(' . $postId . ', 10, 0)');

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
        $parentCommentId = $this->httpGet('parentCommentId');
        $fb = new FormBuilder();

        $newCommentFormUrl = ['page' => 'UserModule:Posts', 'action' => 'newComment', 'postId' => $postId];

        if($parentCommentId !== null) {
            $newCommentFormUrl['parentCommentId'] = $parentCommentId;
        }

        $fb ->setAction($newCommentFormUrl)
            ->addTextArea('text', 'Comment:', null, true)
            ->addSubmit('Post comment')
        ;

        $this->saveToPresenterCache('form', $fb);

        try {
            $topic = $app->topicManager->getTopicById($post->getTopicId(), $app->currentUser->getId());
        } catch (AException $e) {
            $this->flashMessage($e->getMessage(), 'error');
            $this->redirect(['page' => 'UserModule:Home', 'action' => 'dashboard']);
        }

        $topicTitle = $bwh->checkText($topic->getTitle());

        $topicLink = '<a class="post-title-link" href="?page=UserModule:Topics&action=profile&topicId=' . $topic->getId() . '">' . $topicTitle . '</a>';
        $this->saveToPresenterCache('topic', $topicLink);

        $postDescription = $bwh->checkText($post->getText());
        $this->saveToPresenterCache('postDescription', $postDescription);

        // post data
        $likes = $post->getLikes();
        $likeLink = '<a class="post-data-link" href="?page=UserModule:Posts&action=like&postId=' . $postId . '">Like</a>';
        $unlikeLink = '<a class="post-data-link" href="?page=UserModule:Posts&action=unlike&postId=' . $postId . '">Unlike</a>';
        $liked = $app->postRepository->checkLike($app->currentUser->getId(), $postId);
        $finalLikeLink = '';

        if(!$post->isDeleted()) {
            $finalLikeLink = ' ' . ($liked ? $unlikeLink : $likeLink);
        }

        $author = $app->userRepository->getUserById($post->getAuthorId());
        $authorLink = $app->topicMembershipManager->createUserProfileLinkWithRole($author, $post->getTopicId());

        $reportLink = '';
        if(!$post->isDeleted() && $app->actionAuthorizator->canReportPost($app->currentUser->getId(), $topic->getId())) {
            $reportLink = '<a class="post-data-link" href="?page=UserModule:Posts&action=reportForm&postId=' . $postId . '">Report post</a>';
        }

        $deleteLink = '';

        if($app->actionAuthorizator->canDeletePost($app->currentUser->getId(), $post->getTopicId()) && !$post->isDeleted()) {
            $deleteLink = '<p class="post-data"><a class="post-data-link" href="?page=UserModule:Posts&action=deletePost&postId=' . $postId . '">Delete post</a></p>';
        } else if($post->isDeleted()) {
            $deleteLink = '<p class="post-data">Post deleted</p>';
        }

        [$tagColor, $tagBgColor] = PostTags::getColorByKey($post->getTag());

        $postData = '
            <div>
                <p class="post-data">Likes: ' . $likes . '' . $finalLikeLink . '</p>
                <p class="post-data">Date posted: ' . DateTimeFormatHelper::formatDateToUserFriendly($post->getDateCreated()) . '</p>
                <p class="post-data">Author: ' . $authorLink . '</p>
                <p class="post-data">Tag: ' . PostTags::createTagText(PostTags::toString($post->getTag()), $tagColor, $tagBgColor, false) . '</p>
                <p class="post-data">' . $reportLink . '</p>
                ' . $deleteLink . '
            </div>
        ';

        $this->saveToPresenterCache('postData', $postData);

        if($post->isDeleted()) {
            $this->addExternalScript('js/Reducer.js');
            $this->addScript('reducePostProfile()');
        }

        $imagesCode = [];

        $postImages = $app->fileUploadRepository->getFilesForPost($postId);
        if(!empty($postImages)) {
            foreach($postImages as $postImage) {
                $imagePath = $app->fileUploadManager->createPostImageSourceLink($postImage);
                $imageLink = '<a href="#" onclick="openImage(\'' . $imagePath . '\')"><img src="' . $imagePath . '" height="64px"></a>';

                $imagesCode[] = $imageLink;
            }
        }

        $newImageUploadLink = LinkBuilder::createSimpleLink('Upload image', $this->createURL('uploadImageForm', ['postId' => $postId]), 'post-data-link');

        $newImageUploadSection = '<div class="row">
                <div class="col-md-3"></div>

                <div class="col-md">
                    ' . $newImageUploadLink . '
                </div>

                <div class="col-md-3"></div>
            </div>

            <hr>';

        if(!$app->actionAuthorizator->canUploadFileForPost($app->currentUser->getId(), $post)) {
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
                    <div class="col-md-3"></div>
            
                    <div class="col-md">' . $imagesCode . '</div>
            
                    <div class="col-md-3"></div>
                </div>

                <hr>
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

    public function handleUploadImageForm() {
        $postId = $this->httpGet('postId');

        $links = [];

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
        global $app;

        $postId = $this->httpGet('postId');
        $post = $app->postRepository->getPostById($postId);

        $app->topicRepository->beginTransaction();

        try {
            if(isset($_FILES['image']['name'])) {
                $app->fileUploadManager->uploadPostImage($app->currentUser->getId(), $postId, $post->getTopicId(), $_FILES['image']['name'], $_FILES['image']['tmp_name'], $_FILES['image']);
            } else {
                throw new FileUploadException('No file selected.');
            }

            $app->topicRepository->commit();
            $this->flashMessage('Image uploaded.', 'success');
        } catch(Exception $e) {
            $app->topicRepository->rollback();
            $this->flashMessage('Image could not be uploaded. Reason: ' . $e->getMessage(), 'error');
        }

        $this->redirect($this->createURL('profile', ['postId' => $postId]));
    }

    public function actionCreateNewCommentForm() {
        $postId = $this->httpGet('postId');
        $parentCommentId = $this->httpGet('commentId');

        $fb = new FormBuilder();

        $fb ->setAction(['page' => 'UserModule:Posts', 'action' => 'newComment', 'postId' => $postId, 'parentCommentId' => $parentCommentId, 'isFormSubmit' => '1'])
            ->addTextArea('text', 'Comment:', null, true)
            ->addSubmit('Post comment')
        ;

        $this->ajaxSendResponse(['form' => $fb->render()]);
    }

    public function actionLikePostComment() {
        global $app;

        $commentId = $this->httpGet('commentId');
        $toLike = $this->httpGet('toLike');
        $userId = $app->currentUser->getId();

        $comment = $app->postCommentRepository->getCommentById($commentId);

        $post = $app->postRepository->getPostById($comment->getPostId());
        $postLink = LinkBuilder::createSimpleLinkObject($post->getTitle(), $this->createURL('profile', ['postId' => $post->getId()]), 'post-data-link');

        $authorLink = LinkBuilder::createSimpleLinkObject($app->currentUser->getUsername(), ['page' => 'UserModule:Users', 'action' => 'profile', 'userId' => $app->currentUser->getId()], 'post-data-link');

        $liked = false;
        
        $app->postCommentRepository->beginTransaction();

        try {
            if($toLike == 'true') {
                $app->postCommentRepository->likeComment($userId, $commentId);
                $liked = true;

                if($app->currentUser->getId() != $comment->getAuthorId()) {
                    $app->notificationManager->createNewCommentLikeNotification($comment->getAuthorId(), $postLink, $authorLink);
                }
            } else {
                $app->postCommentRepository->unlikeComment($userId, $commentId);
            }

            $app->postCommentRepository->commit();
        } catch(AException $e) {
            $app->postCommentRepository->rollback();
            
            $this->flashMessage('Comment could not be ' . $liked ? 'liked' : 'unliked' . '.', 'error');
            
            $liked = false;
        }
            
        $likes = $app->postCommentRepository->getLikes($commentId);
        
        $link = '<a class="post-like" style="cursor: pointer" onclick="likePostComment(' . $commentId .', ' . ($liked ? 'false' : 'true') . ')">' . ($liked ? 'Unlike' : 'Like') . '</a>';

        $this->ajaxSendResponse(['link' => $link, 'likes' => $likes]);
    }

    public function actionLoadCommentsForPost() {
        global $app;
        
        $postId = $this->httpGet('postId');
        $limit = $this->httpGet('limit');
        $offset = $this->httpGet('offset');

        $post = $app->postRepository->getPostById($postId);

        $comments = $app->postCommentRepository->getLatestCommentsForPostId($postId, $limit, $offset, !$post->isDeleted());
        $commentCount = $app->postCommentRepository->getCommentCountForPostId($postId, !$post->isDeleted());

        $allComments = $app->postCommentRepository->getCommentsForPostId($postId);

        $commentIds = [];
        foreach($allComments as $comment) {
            $commentIds[] = $comment->getId();
        }

        $likedComments = $app->postCommentRepository->getLikedCommentsForUser($app->currentUser->getId(), $commentIds);

        $childrenComments = $app->postCommentRepository->getCommentsThatHaveAParent($postId);

        $code = [];

        if(empty($comments)) {
            return $this->ajaxSendResponse(['comments' => 'No comments found', 'loadMoreLink' => '']);
        }

        $bwh = new BannedWordsHelper($app->contentRegulationRepository);

        foreach($comments as $comment) {
            $code[] = $this->createPostComment($postId, $comment, $likedComments, $bwh, $childrenComments);
        }

        if(($offset + $limit) >= $commentCount) {
            $loadMoreLink = '';
        } else {
            $loadMoreLink = '<a class="post-data-link" style="cursor: pointer" onclick="loadCommentsForPost(' . $postId . ', ' . $limit . ', ' . ($offset + $limit) . ')">Load more</a>';
        }

        $this->ajaxSendResponse(['comments' => implode('<hr>', $code), 'loadMoreLink' => $loadMoreLink]);
    }

    private function createPostComment(int $postId, PostCommentEntity $comment, array $likedComments, BannedWordsHelper $bwh, array $childComments, bool $parent = true) {
        global $app;

        $post = $app->postRepository->getPostById($postId);

        $author = $app->userRepository->getUserById($comment->getAuthorId());
        $userProfileLink = $app->topicMembershipManager->createUserProfileLinkWithRole($author, $post->getTopicId());

        $liked = in_array($comment->getId(), $likedComments);
        if(!$post->isDeleted()) {
            $likeLink = '<a class="post-like" style="cursor: pointer" onclick="likePostComment(' . $comment->getId() .', ' . ($liked ? 'false' : 'true') . ')">' . ($liked ? 'Unlike' : 'Like') . '</a>';
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

        if(!$post->isDeleted() && $app->actionAuthorizator->canReportPost($app->currentUser->getId(), $post->getTopicId())) {
            $reportForm = ' | <a class="post-data-link" href="?page=UserModule:Posts&action=reportComment&commentId=' . $comment->getId() . '">Report</a>';
        } else {
            $reportForm = '';
        }
        $deleteLink = '';
        
        if($app->actionAuthorizator->canDeleteComment($app->currentUser->getId(), $post->getTopicId()) && !$post->isDeleted()) {
            $deleteLink = ' | <a class="post-data-link" href="?page=UserModule:Posts&action=deleteComment&commentId=' . $comment->getId() . '&postId=' . $postId . '">Delete</a>';
        }

        $text = $bwh->checkText($comment->getText());

        $matches = [];
        preg_match_all("/[@]\w*/m", $text, $matches);

        $matches = $matches[0];

        $post = $app->postRepository->getPostById($postId);

        $users = [];
        foreach($matches as $match) {
            $username = substr($match, 1);
            $user = $app->userRepository->getUserByUsername($username);
            $link = $app->topicMembershipManager->createUserProfileLinkWithRole($user, $post->getTopicId(), '@');
            
            $users[$match] = $link;
        }

        foreach($users as $k => $v) {
            $text = str_replace($k, $v, $text);
        }

        $pattern = "/\[(.*?),\s*(https?:\/\/[^\]]+)\]/";
        $replacement = '<a class="post-text-link" href="$2" target="_blank">$1</a>';

        $text = preg_replace($pattern, $replacement, $text);

        $code = '
            <div class="row' . ($parent ? '' : ' post-comment-border') . '" id="post-comment-' . $comment->getId() . '">
                ' . ($parent ? '' : '<div class="col-md-1"></div>') . '
                <div class="col-md">
                    <div>
                        <p class="post-text">' . $text . '</p>
                        <p class="post-data">Likes: <span id="post-comment-' . $comment->getId() . '-likes">' . $comment->getLikes() . '</span> <span id="post-comment-' . $comment->getId() . '-link">' . $likeLink . '</span>
                                            | Author: ' . $userProfileLink . ' | Date: ' . DateTimeFormatHelper::formatDateToUserFriendly($comment->getDateCreated()) . '' . $reportForm . $deleteLink . '
                        </p>
                        ' . ($post->isDeleted() ? '' : '<a class="post-data-link" id="post-comment-' . $comment->getId() . '-add-comment-link" style="cursor: pointer" onclick="createNewCommentForm(' . $comment->getId() . ', ' . $postId . ')">Add comment</a>') . '
                    </div>
                    <div class="row">
                        <div class="col-md-2"></div>

                        <div class="col-md" id="form">
                            <div id="post-comment-' . $comment->getId() . '-comment-form"></div>
                        </div>
                        
                        <div class="col-md-2"></div>
                    </div>
                    ' . implode('', $childCommentsCode) .  '
                    ' . ($parent ? '' : '<div class="col-md-1"></div>') . '
                </div>
            </div>
            <br>
        ';

        return $code;
    }

    public function handleNewComment(FormResponse $fr) {
        global $app;

        $text = $fr->text;
        $postId = $this->httpGet('postId');
        $authorId = $app->currentUser->getId();
        $parentCommentId = $this->httpGet('parentCommentId');

        $post = $app->postRepository->getPostById($postId);
        $postLink = LinkBuilder::createSimpleLinkObject($post->getTitle(), ['page' => 'UserModule:Posts', 'action' => 'profile', 'postId' => $postId], 'post-data-link');

        $authorLink = UserEntity::createUserProfileLink($app->currentUser, true);

        $app->postCommentRepository->beginTransaction();
        
        try {
            $app->postCommentRepository->createNewComment($postId, $authorId, $text, $parentCommentId);

            if($post->getAuthorId() != $authorId) {
                $app->notificationManager->createNewPostCommentNotification($post->getAuthorId(), $postLink, $authorLink);
            }

            $app->postCommentRepository->commit();

            $this->flashMessage('Comment posted.', 'success');
        } catch (AException $e) {
            $app->postCommentRepository->rollback();

            $this->flashMessage('Comment could not be created. Error: ' . $e->getMessage(), 'error');
        }
        
        $this->redirect(['page' => 'UserModule:Posts', 'action' => 'profile', 'postId' => $postId]);
    }

    public function handleReportForm(?FormResponse $fr = null) {
        global $app;

        $postId = $this->httpGet('postId');
        
        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $category = $fr->category;
            $description = $fr->description;
            $userId = $app->currentUser->getId();

            $app->reportRepository->createPostReport($userId, $postId, $category, $description);

            $this->flashMessage('Post reported.', 'success');
            $this->redirect(['page' => 'UserModule:Posts', 'action' => 'profile', 'postId' => $postId]);
        } else {
            $post = $app->postRepository->getPostById($postId);
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
        global $app;

        $commentId = $this->httpGet('commentId');
        $comment = $app->postCommentRepository->getCommentById($commentId);

        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $category = $fr->category;
            $description = $fr->description;
            $userId = $app->currentUser->getId();

            $app->reportRepository->createCommentReport($userId, $commentId, $category, $description);

            $this->flashMessage('Comment reported.', 'success');
            $this->redirect(['page' => 'UserModule:Posts', 'action' => 'profile', 'postId' => $comment->getPostId()]);
        } else {
            $this->saveToPresenterCache('comment', $comment);

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
        $comment = $this->loadFromPresenterCache('comment');
        $form = $this->loadFromPresenterCache('form');
        $links = $this->loadFromPresenterCache('links');

        $this->template->comment_id = $comment->getId();
        $this->template->form = $form;
        $this->template->links = $links;
    }

    public function handleDeleteComment(?FormResponse $fr = null) {
        global $app;

        $commentId = $this->httpGet('commentId');
        $postId = $this->httpGet('postId');
        
        if($this->httpGet('isSubmit') == '1') {
            $post = $app->postRepository->getPostById($postId);
            $comment = $app->postCommentRepository->getCommentById($commentId);
            $postLink = LinkBuilder::createSimpleLinkObject($post->getTitle(), $this->createURL('profile', ['postId' => $postId]), 'post-data-link');
            $userLink = UserEntity::createUserProfileLink($app->currentUser, true);

            $app->postRepository->beginTransaction();

            try {
                $app->contentManager->deleteComment($commentId);

                $app->notificationManager->createNewCommentDeletedNotification($comment->getAuthorId(), $postLink, $userLink);

                $app->postRepository->commit();

                $this->flashMessage('Comment #' . $commentId . ' has been deleted.', 'success');
            } catch(Exception $e) {
                $app->postRepository->rollback();

                $this->flashMessage('Comment #' . $commentId . ' could not be deleted. Reason: ' . $e->getMessage(), 'error');
            }
            
            $this->redirect(['action' => 'profile', 'postId' => $postId]);
        } else {
            $fb = new FormBuilder();
            
            $fb ->setAction(['page' => 'UserModule:Posts', 'action' => 'deleteComment', 'isSubmit' => '1', 'commentId' => $commentId, 'postId' => $postId])
                ->addSubmit('Delete comment')
                ->addButton('&larr; Go back', 'location.href = \'?page=UserModule:Posts&action=profile&postId=' . $postId . '\';')
            ;

            $this->saveToPresenterCache('form', $fb);
        }
    }

    public function renderDeleteComment() {
        $form = $this->loadFromPresenterCache('form');

        $this->template->form = $form;
    }

    public function handleDeletePost(?FormResponse $fr = null) {
        global $app;

        $postId = $this->httpGet('postId');

        if($this->httpGet('isSubmit') == '1') {
            $post = $app->postRepository->getPostById($postId);
            $postLink = LinkBuilder::createSimpleLinkObject($post->getTitle(), $this->createURL('profile', ['postId' => $postId]), 'post-data-link');
            $userLink = UserEntity::createUserProfileLink($app->currentUser, true);

            $app->postRepository->beginTransaction();

            try {
                $app->contentManager->deletePost($postId);

                $app->notificationManager->createNewPostDeletedNotification($post->getAuthorId(), $postLink, $userLink);

                $app->postRepository->commit();

                $this->flashMessage('Post #' . $postId . ' has been deleted.', 'success');
            } catch(Exception $e) {
                $app->postRepository->rollback();

                $this->flashMessage('Post #' . $postId . ' could not be deleted. Reason: ' . $e->getMessage(), 'error');
            }

            $this->redirect(['action' => 'profile', 'postId' => $postId]);
        } else {
            $fb = new FormBuilder();
            
            $fb ->setAction(['page' => 'UserModule:Posts', 'action' => 'deletePost', 'isSubmit' => '1', 'postId' => $postId])
                ->addSubmit('Delete post')
                ->addButton('&larr; Go back', 'location.href = \'?page=UserModule:Posts&action=profile&postId=' . $postId . '\';')
            ;

            $this->saveToPresenterCache('form', $fb);
        }
    }

    public function renderDeletePost() {
        $form = $this->loadFromPresenterCache('form');

        $this->template->form = $form;
    }

    public function handleLike() {
        global $app;

        $postId = $this->httpGet('postId', true);
        $post = $app->postRepository->getPostById($postId);

        $postLink = LinkBuilder::createSimpleLinkObject($post->getTitle(), ['page' => 'UserModule:Posts', 'action' => 'profile', 'postId' => $postId], 'post-data-link');
        
        $app->postRepository->beginTransaction();

        try {
            $app->postRepository->likePost($app->currentUser->getId(), $postId);
            $app->postRepository->updatePost($postId, ['likes' => $post->getLikes() + 1]);

            if($app->currentUser->getId() != $post->getAuthorId()) {
                $app->notificationManager->createNewPostLikeNotification($post->getAuthorId(), $postLink, UserEntity::createUserProfileLink($app->currentUser, true));
            }

            $cm = new CacheManager($app->logger);
            $cm->invalidateCache('posts');

            $app->postRepository->commit();
        } catch(AException $e) {
            $app->postRepository->rollback();
            $this->flashMessage('Could not like post #' . $postId . '. Reason: ' . $e->getMessage(), 'error');
        }

        $this->redirect(['page' => 'UserModule:Posts', 'action' => 'profile', 'postId' => $postId]);
    }

    public function handleUnlike() {
        global $app;

        $postId = $this->httpGet('postId', true);
        $post = $app->postRepository->getPostById($postId);

        $app->postRepository->unlikePost($app->currentUser->getId(), $postId);
        $app->postRepository->updatePost($postId, ['likes' => $post->getLikes() - 1]);

        $cm = new CacheManager($app->logger);

        $cm->invalidateCache('posts');

        $this->redirect(['page' => 'UserModule:Posts', 'action' => 'profile', 'postId' => $postId]);
    }
}

?>