<?php

namespace App\Modules\AdminModule;

use App\Constants\UserProsecutionType;
use App\Core\Caching\CacheNames;
use App\Core\Datetypes\DateTime;
use App\Core\DB\DatabaseRow;
use App\Core\HashManager;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Helpers\GridHelper;
use App\Managers\EntityManager;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class ManageUsersPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ManageUsersPresenter', 'Users management');
    }

    public function startup() {
        parent::startup();

        if(!$this->app->sidebarAuthorizator->canManageUsers($this->getUserId())) {
            $this->flashMessage('You are not authorized to visit this section.');
            $this->redirect(['page' => 'AdminModule:Manage', 'action' => 'dashboard']);
        }
    }

    public function createComponentGrid() {
        $grid = $this->getGridBuilder();

        $grid->createDataSourceFromQueryBuilder($this->app->userRepository->composeQueryForUsers(), 'userId');
        $grid->setGridName(GridHelper::GRID_USERS);

        $grid->addColumnText('username', 'Username');
        $grid->addColumnText('email', 'Email');
        $grid->addColumnDatetime('dateCreated', 'Date created');
        $grid->addColumnBoolean('isAdmin', 'Is administrator?');
        $grid->addColumnBoolean('canLogin', 'Can login?');

        $profile = $grid->addAction('profile');
        $profile->onCanRender[] = function(DatabaseRow $row, Row $_row) {
            return true;
        };
        $profile->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            return LinkBuilder::createSimpleLink('Profile', $this->createFullURL('UserModule:Users', 'profile', ['userId' => $primaryKey]), 'grid-link');
        };

        $setAdmin = $grid->addAction('setAdmin');
        $setAdmin->onCanRender[] = function(DatabaseRow $row, Row $_row) {
            if($row->username == 'service_user' || $row->username == 'admin') {
                return false;
            }

            return true;
        };
        $setAdmin->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            if($row->isAdmin) {
                return LinkBuilder::createSimpleLink('Unset as administrator', $this->createURL('unsetAdmin', ['userId' => $primaryKey]), 'grid-link');
            } else {
                return LinkBuilder::createSimpleLink('Set as administrator', $this->createURL('setAdmin', ['userId' => $primaryKey]), 'grid-link');
            }
        };

        return $grid;
    }

    public function renderList() {
        $newUserLink = '<a class="post-data-link" href="?page=AdminModule:ManageUsers&action=newForm">New user</a>';
        $this->template->links = [$newUserLink];
    }

    public function handleUnsetAdmin(?FormResponse $fr = null) {
        $userId = $this->httpGet('userId', true);

        try {
            $user = $this->app->userManager->getUserById($userId);
        } catch(AException $e) {
            $this->flashMessage('Could not find user. Reason: ' . $e->getMessage(), 'error');
            $this->redirect(['page' => 'AdminModule:ManageUsers', 'action' => 'list']);
        }

        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $password = $fr->password;

            try {
                $this->app->userAuth->authUser($password);
            } catch (AException $e) {
                $this->flashMessage('You entered bad credentials. Please try again.', 'error');
                $this->redirect(['page' => 'AdminModule:ManageUsers', 'action' => 'unsetAdmin', 'userId' => $userId]);
            }

            try {
                $this->app->userRepository->beginTransaction();

                if(!$this->app->userRepository->updateUser($userId, ['isAdmin' => '0'])) {
                    throw new GeneralException('User could not be updated.');
                }

                $this->app->logger->warning('User #' . $userId . ' is not administrator. User #' . $this->getUserId() . ' is responsible for this action.', __METHOD__);

                $cache = $this->cacheFactory->getCache(CacheNames::USERS);
                $cache->invalidate();

                $this->app->userRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('User ' . $user->getUsername() . ' is not an administrator.', 'info');
            } catch(AException $e) {
                $this->app->userRepository->rollback();

                $this->flashMessage('Could not unset user as administrator. Reason: ' . $e->getMessage(), 'error');
            }

            $this->redirect(['page' => 'AdminModule:ManageUsers', 'action' => 'list']);
        } else {
            $fb = new FormBuilder();
            
            $fb ->setAction(['page' => 'AdminModule:ManageUsers', 'action' => 'unsetAdmin', 'isSubmit' => '1', 'userId' => $userId])
                ->addPassword('password', 'Your password:', null, true)
                ->addSubmit('Unset user \'' . $user->getUsername() . '\' as administrator')
                ->addButton('Back', 'location.href = \'?page=AdminModule:ManageUsers&action=list\'', 'formSubmit');
            ;

            $this->saveToPresenterCache('form', $fb);
        }
    }

    public function renderUnsetAdmin() {
        $form = $this->loadFromPresenterCache('form');

        $this->template->form = $form;
    }

    public function handleSetAdmin(?FormResponse $fr = null) {
        $userId = $this->httpGet('userId', true);
        
        try {
            $user = $this->app->userManager->getUserById($userId);
        } catch(AException $e) {
            $this->flashMessage('Could not find user. Reason: ' . $e->getMessage(), 'error');
            $this->redirect(['page' => 'AdminModule:ManageUsers', 'action' => 'list']);
        }

        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $password = $fr->password;

            try {
                $this->app->userAuth->authUser($password);
            } catch (AException $e) {
                $this->flashMessage('You entered bad credentials. Please try again.', 'error');
                $this->redirect(['page' => 'AdminModule:ManageUsers', 'action' => 'setAdmin', 'userId' => $userId]);
            }

            try {
                $this->app->userRepository->beginTransaction();

                if(!$this->app->userRepository->updateUser($userId, ['isAdmin' => '1'])) {
                    throw new GeneralException('User could not be updated.');
                }

                $this->app->logger->warning('User #' . $userId . ' is now administrator. User #' . $this->getUserId() . ' is responsible for this action.', __METHOD__);

                $cache = $this->cacheFactory->getCache(CacheNames::USERS);
                $cache->invalidate();

                $this->app->userRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('User ' . $user->getUsername() . ' is now an administrator.', 'info');
            } catch(AException $e) {
                $this->app->userRepository->rollback();
                
                $this->flashMessage('Could not set user as administrator. Reason: ' . $e->getMessage(), 'error');
            }

            $this->redirect(['page' => 'AdminModule:ManageUsers', 'action' => 'list']);
        } else {
            $fb = new FormBuilder();
            
            $fb ->setAction(['page' => 'AdminModule:ManageUsers', 'action' => 'setAdmin', 'isSubmit' => '1', 'userId' => $userId])
                ->addPassword('password', 'Your password:', null, true)
                ->addSubmit('Set user \'' . $user->getUsername() . '\' as administrator')
                ->addButton('Back', 'location.href = \'?page=AdminModule:ManageUsers&action=list\'', 'formSubmit')
            ;

            $this->saveToPresenterCache('form', $fb);
        }
    }

    public function renderSetAdmin() {
        $form = $this->loadFromPresenterCache('form');

        $this->template->form = $form;
    }
    
    public function handleWarnUser(?FormResponse $fr = null) {
        $userId = $this->httpGet('userId', true);
        $reportId = $this->httpGet('reportId', true);
        
        try {
            $user = $this->app->userManager->getUserById($userId);
        } catch(AException $e) {
            $this->flashMessage('Could not find user. Reason: ' . $e->getMessage(), 'error');
            $this->redirect(['page' => 'AdminModule:FeedbackReports', 'action' => 'profile', 'reportId' => $reportId]);
        }

        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $reason = $fr->description;

            try {
                $this->app->userProsecutionRepository->beginTransaction();

                $expire = new DateTime();
                $expire->modify('+7d');
                $expire = $expire->getResult();

                $this->app->userProsecutionRepository->createNewProsecution($userId, UserProsecutionType::WARNING, $reason, DateTime::now(), $expire);

                $this->app->userProsecutionRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('User \'' . $user->getUsername() . '\' has been warned.');
            } catch(AException $e) {
                $this->app->userProsecutionRepository->rollback();

                $this->flashMessage('Could not warn user. Reason: ' . $e->getMessage(), 'error');
            }

            $this->redirect(['page' => 'AdminModule:FeedbackReports', 'action' => 'profile', 'reportId' => $reportId]);
        } else {
            $fb = new FormBuilder();

            $fb ->setAction(['page' => 'AdminModule:ManageUsers', 'action' => 'warnUser', 'isSubmit' => '1', 'userId' => $userId, 'reportId' => $reportId])
                ->addTextArea('description', 'Reason:', null, true)
                ->addSubmit('Warn user \'' . $user->getUsername() .  '\'')
                ->addButton('Back', 'location.href = \'?page=AdminModule:FeedbackReports&action=profile&reportId=' . $reportId . '\'', 'formSubmit')
            ;

            $this->saveToPresenterCache('form', $fb);
        }
    }

    public function renderWarnUser() {
        $form = $this->loadFromPresenterCache('form');

        $this->template->form = $form;
    }

    public function handleBanUser(?FormResponse $fr = null) {
        $userId = $this->httpGet('userId', true);
        $reportId = $this->httpGet('reportId');
        
        try {
            $user = $this->app->userManager->getUserById($userId);
        } catch(AException $e) {
            $this->flashMessage('Could not find user. Reason: ' . $e->getMessage(), 'error');
            $this->redirect(['page' => 'AdminModule:FeedbacReports', 'action' => 'profile', 'reportId' => $reportId]);
        }

        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $reason = $fr->description;
            $type = $fr->type;
            $startDate = $fr->startDate;
            $endDate = $fr->endDate;

            if($type == UserProsecutionType::PERMA_BAN) {
                try {
                    $this->app->userProsecutionRepository->beginTransaction();

                    $this->app->userProsecutionManager->permaBanUser($userId, $this->getUserId(), $reason);

                    $this->app->userProsecutionRepository->commit($this->getUserId(), __METHOD__);
                } catch(AException $e) {
                    $this->flashMessage('Could not ban user \'' . $user->getUsername() . '\'. Reason: ' . $e->getMessage(), 'error');
                    $this->redirect(['page' => 'AdminModule:FeedbacReports', 'action' => 'profile', 'reportId' => $reportId]);
                }
            } else {
                try {
                    $this->app->userProsecutionRepository->beginTransaction();

                    $this->app->userProsecutionManager->banUser($userId, $this->getUserId(), $reason, $startDate, $endDate);

                    $this->app->userProsecutionRepository->commit($this->getUserId(), __METHOD__);
                } catch(AException $e) {
                    $this->flashMessage('Could not ban user \'' . $user->getUsername() . '\'. Reason: ' . $e->getMessage(), 'error');
                    $this->redirect(['page' => 'AdminModule:FeedbacReports', 'action' => 'profile', 'reportId' => $reportId]);
                }
            }

            $this->flashMessage('User \'' . $user->getUsername() . '\' has been banned.');
            $this->redirect(['page' => 'AdminModule:FeedbacReports', 'action' => 'profile', 'reportId' => $reportId]);
        } else {
            $date = new DateTime();

            $fb = new FormBuilder();

            $fb ->setAction(['page' => 'AdminModule:ManageUsers', 'action' => 'banUser', 'isSubmit' => '1', 'userId' => $userId])
                ->addTextArea('description', 'Reason:', null, true)
                ->addSelect('type', 'Type:', [['value' => UserProsecutionType::BAN, 'text' => 'Ban'], ['value' => UserProsecutionType::PERMA_BAN, 'text' => 'Perma ban']], true)
                ->addDatetime('startDate', 'Date from:', $date->getResult(), true)
                ->addDatetime('endDate', 'Date to:', $date->getResult(), true)
                ->addSubmit('Ban user \'' . $user->getUsername() .  '\'')
                ->addButton('Back', 'location.href = \'?page=AdminModule:FeedbackReports&action=profile&reportId=' . $reportId . '\'', 'formSubmit')
                ->addJSHandler('js/UserBanFormHandler.js')
            ;

            $this->saveToPresenterCache('form', $fb);
        }
    }

    public function renderBanUser() {
        $form = $this->loadFromPresenterCache('form');

        $this->template->form = $form;
    }

    public function handleNewForm(?FormResponse $fr = null) {
        if($this->httpGet('isSubmit') == '1') {
            $username = $fr->username;
            $password = $fr->password;
            $email = $fr->email;
            $isAdmin = $fr->evalBool($fr->isAdmin, 'on');

            if($email == '') {
                $email = null;
            }

            $password = HashManager::hashPassword($password);

            try {
                $this->app->userRepository->beginTransaction();

                $userId = $this->app->userRepository->createEntityId(EntityManager::USERS);

                $this->app->userRepository->createNewUser($userId, $username, $password, $email, $isAdmin);

                $this->app->userRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('User <i>' . $username . '</i> has been created.', 'success');
            } catch(AException $e) {
                $this->app->userRepository->rollback();

                $this->flashMessage('Could not create user. Reason: ' . $e->getMessage(), 'error');
            }

            $this->redirect(['action' => 'list']);
        } else {
            $fb = new FormBuilder();

            $fb ->setAction(['page' => 'AdminModule:ManageUsers', 'action' => 'newForm', 'isSubmit' => '1'])
                ->addTextInput('username', 'Username:', null, true)
                ->addEmailInput('email', 'Email:', null, false)
                ->addPassword('password', 'Password:', null, true)
                ->addCheckbox('isAdmin', 'Administrator?')
                ->addSubmit('Create', false, true)
            ;

            $this->saveToPresenterCache('form', $fb);

            $links = [
                LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('list'), 'post-data-link')
            ];

            $this->saveToPresenterCache('links', $links);
        }
    }

    public function renderNewForm() {
        $form = $this->loadFromPresenterCache('form');
        $links = $this->loadFromPresenterCache('links');

        $this->template->form = $form;
        $this->template->links = $links;
    }
}

?>