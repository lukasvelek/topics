<?php

namespace App\Managers;

use App\Exceptions\NonExistingEntityException;
use App\Logger\Logger;
use App\Repositories\PostCommentRepository;
use App\Repositories\PostRepository;

class PostManager extends AManager {
    private PostRepository $pr;
    private PostCommentRepository $pcr;

    public function __construct(
        Logger $logger,
        EntityManager $entityManager,
        PostRepository $pr,
        PostCommentRepository $pcr
    ) {
        parent::__construct($logger, $entityManager);

        $this->pr = $pr;
        $this->pcr = $pcr;
    }

    public function getPostById(string $userId, string $postId) {
        $post = $this->pr->getPostById($postId);

        if($post === null) {
            throw new NonExistingEntityException('Post \'#' . $postId . '\' does not exist.');
        }

        return $post;
    }

    public function getListOfPostsWithHashtagsInDescriptions(int $limit, int $offset) {
        $query = $this->pr->composeQueryForPostsWithHashtagsInDescription();
        
        if($limit > 0) {
            $query->limit($limit);
        }
        if($offset > 0) {
            $query->offset($offset);
        }

        $query->execute();

        $hashtags = [];
        while($row = $query->fetchAssoc()) {
            $hashtagsTmp = [];
            preg_match_all("/[&]*[#][a-zA-Z0-1]\w*[;]*/m", $row['description'], $hashtagsTmp);
            $hashtagsTmp = $hashtagsTmp[0];

            foreach($hashtagsTmp as $htmp) {
                if(str_contains($htmp, '&')) {
                    continue;
                } else {
                    $hashtags[] = $htmp;
                }
            }
        }

        return $hashtags;
    }

    public function getListOfPostCommentsWithHashtags(int $limit, int $offset) {
        $query = $this->pcr->composeQueryForPostCommentsWithHashtags();

        if($limit > 0) {
            $query->limit($limit);
        }
        if($offset > 0) {
            $query->offset($offset);
        }

        $query->execute();

        $hashtags = [];
        while($row = $query->fetchAssoc()) {
            $hashtagsTmp = [];
            preg_match_all("/[&]*[#][a-zA-Z0-1]\w*[;]*/m", $row['commentText'], $hashtagsTmp);
            $hashtagsTmp = $hashtagsTmp[0];

            foreach($hashtagsTmp as $htmp) {
                if(str_contains($htmp, '&')) {
                    continue;
                } else {
                    $hashtags[] = $htmp;
                }
            }
        }

        return $hashtags;
    }
}

?>