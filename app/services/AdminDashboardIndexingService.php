<?php

namespace App\Services;

use App\Core\Datetypes\DateTime;
use App\Core\HashManager;
use App\Core\ServiceManager;
use App\Exceptions\AException;
use App\Logger\Logger;
use App\Managers\EntityManager;
use App\Repositories\PostRepository;
use App\Repositories\TopicRepository;
use Exception;

class AdminDashboardIndexingService extends AService {
    private const AGE = '-1d';

    private TopicRepository $tr;
    private PostRepository $pr;

    public function __construct(Logger $logger, ServiceManager $serviceManager, TopicRepository $tr, PostRepository $pr) {
        parent::__construct('AdminDashboardIndexing', $logger, $serviceManager);

        $this->tr = $tr;
        $this->pr = $pr;
    }

    public function run() {
        try {
            $this->serviceStart();

            $this->innerRun();

            $this->serviceStop();
        } catch(AException|Exception $e) {
            try {
                $this->serviceStop();
            } catch(AException|Exception $e2) {}
            
            $this->logError($e->getMessage());
            
            throw $e;
        }
    }

    private function innerRun() {
        $data = [];

        $data['mostActiveTopics'] = serialize($this->getMostActiveTopics());
        $data['mostActivePosts'] =  serialize($this->getMostActivePosts());
        $data['mostActiveUsers'] =  serialize($this->getMostActiveUsers());

        $this->updateData($data);
        $this->deleteOldData();
    }

    private function updateData(array $data) {
        $qb = $this->tr->getQb();

        //$dataId = HashManager::createEntityId();
        $dataId = $this->tr->createEntityId(EntityManager::ADMIN_DASHBOARD_WIDGETS_GRAPH_DATA);

        $data = array_merge([$dataId], $data);

        $qb ->insert('admin_dashboard_widgets_graph_data', ['dataId', 'mostActiveTopics', 'mostActivePosts', 'mostActiveUsers'])
            ->values($data)
            ->execute();
    }

    private function deleteOldData() {
        $qb = $this->tr->getQb();

        $age = new DateTime();
        $age->modify('-30d');
        $age = $age->getResult();

        $qb ->delete()
            ->from('admin_dashboard_widgets_graph_data')
            ->where('dateCreated <= ?', [$age])
            ->execute();
    }

    private function getMostActiveTopics() {
        $age = new DateTime();
        $age->modify(self::AGE);
        $age = $age->getResult();

        $sql = "SELECT topicId, COUNT(postId) AS cnt FROM posts WHERE dateCreated >= '$age' GROUP BY topicId ORDER BY cnt DESC";

        $result = $this->tr->sql($sql);

        $data = [];
        foreach($result as $row) {
            if(count($data) == 10) {
                break;
            }
            $data[$row['topicId']] = $row['cnt'];
        }

        return $data;
    }

    private function getMostActivePosts() {
        $age = new DateTime();
        $age->modify(self::AGE);
        $age = $age->getResult();

        $sql = "SELECT postId, COUNT(commentId) AS cnt FROM post_comments WHERE dateCreated >= '$age' GROUP BY postId ORDER BY cnt DESC";

        $result = $this->pr->sql($sql);

        $data = [];
        foreach($result as $row) {
            if(count($data) == 10) {
                break;
            }
            $data[$row['postId']] = $row['cnt'];
        }

        return $data;
    }

    private function getMostActiveUsers() {
        $age = new DateTime();
        $age->modify(self::AGE);
        $age = $age->getResult();

        $sql = "SELECT authorId, COUNT(commentId) AS cnt FROM post_comments WHERE dateCreated >= '$age' GROUP BY authorId ORDER BY cnt DESC";

        $result = $this->pr->sql($sql);

        $data = [];
        foreach($result as $row){
            if(count($data) == 10) {
                break;
            }
            $data[$row['authorId']] = $row['cnt'];
        }

        return $data;
    }
}

?>