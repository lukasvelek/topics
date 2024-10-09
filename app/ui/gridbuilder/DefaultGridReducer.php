<?php

namespace App\UI\GridBuilder;

use App\Helpers\DateTimeFormatHelper;
use App\Repositories\PostRepository;
use App\Repositories\TopicRepository;
use App\Repositories\UserRepository;
use App\UI\LinkBuilder;

class DefaultGridReducer implements IGridReducer {
    private UserRepository $userRepository;
    private TopicRepository $topicRepository;
    private PostRepository $postRepository;

    public function __construct(
        UserRepository $userRepository,
        TopicRepository $topicRepository,
        PostRepository $postRepository
    ) {
        $this->userRepository = $userRepository;
        $this->topicRepository = $topicRepository;
        $this->postRepository = $postRepository;
    }

    public function applyReducer(GridBuilder &$grid) {
        $this->processDates($grid);
        $this->processEntities($grid);
    }

    private function processDates(GridBuilder &$grid) {
        $cols = $grid->getColumns();

        foreach($cols as $key => $value) {
            if(str_contains(strtolower($key), 'date')) {
                $actionName = 'get' . ucfirst($key);

                $grid->addOnColumnRender($key, function(Cell $cell, object $object) use ($key, $actionName) {
                    $date = null;
                    if(method_exists($object, $actionName)) {
                        $date = $object->$actionName();
                    } else if(isset($object->$key)) {
                        $date = $object->$key;
                    }

                    if($date === null) {
                        return null;
                    }

                    return DateTimeFormatHelper::formatDateToUserFriendly($date);
                });
                $grid->addOnExportRender($key, function(object $object) use ($key, $actionName) {
                    $date = null;

                    if(method_exists($object, $actionName)) {
                        $date = $object->$actionName();
                    } else if(isset($object->$key)) {
                        $date = $object->$key;
                    }

                    if($date === null) {
                        return '-';
                    }

                    return DateTimeFormatHelper::formatDateToUserFriendly($date);
                });
            }
        }
    }

    private function processEntities(GridBuilder &$grid) {
        $cols = $grid->getColumns();

        foreach($cols as $key => $value) {
            $actionName = 'get' . ucfirst($key);
            switch($key) {
                case 'topicId':
                    $grid->addOnColumnRender($key, function(Cell $cell, object $object) use ($key, $actionName) {
                        $id = null;
                        if(method_exists($object, $actionName)) {
                            $id = $object->$actionName();
                        } else if(isset($object->$key)) {
                            $id = $object->$key;
                        }
    
                        if($id === null) {
                            return null;
                        }
    
                        $entity = $this->topicRepository->getTopicById($id);
    
                        return LinkBuilder::createSimpleLink($entity->getTitle(), ['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $id], 'grid-link');
                    });
                    $grid->addOnExportRender($key, function(object $object) use ($key, $actionName) {
                        $id = null;
                        if(method_exists($object, $actionName)) {
                            $id = $object->$actionName();
                        } else if(isset($object->$key)) {
                            $id = $object->$key;
                        }

                        if($id === null) {
                            return '-';
                        }

                        $entity = $this->topicRepository->getTopicById($id);

                        return $entity->getTitle();
                    });
                    break;

                case 'userId':
                    $grid->addOnColumnRender($key, function(Cell $cell, object $object) use ($key, $actionName) {
                        $id = null;
                        if(method_exists($object, $actionName)) {
                            $id = $object->$actionName();
                        } else if(isset($object->$key)) {
                            $id = $object->$key;
                        }

                        if($id === null) {
                            return null;
                        }

                        $entity = $this->userRepository->getUserById($id);

                        return LinkBuilder::createSimpleLink($entity->getUsername(), ['page' => 'UserModule:Users', 'action' => 'profile', 'userId' => $id], 'grid-link');
                    });
                    $grid->addOnExportRender($key, function(object $object) use ($key, $actionName) {
                        $id = null;
                        if(method_exists($object, $actionName)) {
                            $id = $object->$actionName();
                        } else if(isset($object->$key)) {
                            $id = $object->$key;
                        }

                        if($id === null) {
                            return null;
                        }

                        $entity = $this->userRepository->getUserById($id);

                        return $entity->getUsername();
                    });
                    break;

                case 'postId':
                    $grid->addOnColumnRender($key, function(Cell $cell, object $object) use ($key, $actionName) {
                        $id = null;
                        if(method_exists($object, $actionName)) {
                            $id = $object->$actionName();
                        } else if(isset($object->$key)) {
                            $id = $object->$key;
                        }

                        if($id === null) {
                            return null;
                        }
                        
                        $entity = $this->postRepository->getPostById($id);

                        return LinkBuilder::createSimpleLink($entity->getTitle(), ['page' => 'UserModule:Posts', 'action' => 'profile', 'postId' => $id], 'grid-link');
                    });
                    $grid->addOnExportRender($key, function(object $object) use ($key, $actionName) {
                        $id = null;
                        if(method_exists($object, $actionName)) {
                            $id = $object->$actionName();
                        } else if(isset($object->$key)) {
                            $id = $object->$key;
                        }

                        if($id === null) {
                            return null;
                        }
                        
                        $entity = $this->postRepository->getPostById($id);

                        return $entity->getTitle();
                    });
                    break;
            }
        }
    }
}

?>