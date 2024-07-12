<?php

namespace App\Modules\AdminModule;

use App\Core\AjaxRequestBuilder;
use App\Core\CacheManager;
use App\Entities\CachedPageEntity;
use App\Entities\UserEntity;
use App\UI\GridBuilder\AjaxGridHelper;
use App\UI\GridBuilder\GridBuilder;
use App\UI\LinkBuilder;

class ManageSystemCachingPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ManageSystemCachingPresenter', 'Manage system caching');

        $this->addBeforeRenderCallback(function() {
            $this->template->sidebar = $this->createManageSidebar();
        });

        global $app;

        if(!$app->sidebarAuthorizator->canManageSystemCaching($app->currentUser->getId())) {
            $this->flashMessage('You are not authorized to visit this section.');
            $this->redirect(['page' => 'AdminModule:Manage', 'action' => 'dashboard']);
        }
    }

    public function actionGetGrid() {
        global $app;

        $type = $this->httpGet('type');
        
        $cm = new CacheManager($app->logger);

        switch($type) {
            case 'pages':
                $files = unserialize($cm->loadCachedFiles('cachedPages'));

                $fileArray = [];
                if($files !== false) {
                    foreach($files as $k => $v) {
                        $fileArray[] = new CachedPageEntity($k, $v);
                    }
                }

                $gb = new GridBuilder();
                $gb->addDataSource($fileArray);
                $gb->addColumns(['name' => 'Name']);
                $gb->addOnColumnRender('name', function(CachedPageEntity $cpe) {
                    $name = $cpe->getName();

                    if(explode('_', $name) == 2) {
                        $module = explode('_', $name)[0];
                        $presenter = explode('_', $name)[1];
                        $presenter = substr($presenter, 0, -9);
            
                        $name = $module . ':' . $presenter;
                    }

                    return $name;
                });
                $gb->addAction(function(CachedPageEntity $cpe) {
                    return LinkBuilder::createSimpleLink('Delete', $this->link('deletePage', ['name' => $cpe->getName()]), 'post-data-link');
                });
                break;

            case 'users':
                    $files = unserialize($cm->loadCachedFiles('users'));

                    $fileArray = [];
                    if($files !== false) {
                        foreach($files as $k => $v) {
                            $fileArray[] = $v;
                        }
                    }

                    $gb = new GridBuilder();
                    $gb->addDataSource($fileArray);
                    $gb->addColumns(['username' => 'Username']);
                    $gb->addAction(function(UserEntity $ue) {
                        return LinkBuilder::createSimpleLink('Delete', $this->link('deleteUser', ['userId' => $ue->getId()]), 'post-data-link');
                    });
                break;

            default:
                $gb = new GridBuilder();
                $gb->addDataSource([]);
                $gb->addColumns(['data' => 'Data']);
                break;
        }
        
        $this->ajaxSendResponse(['grid' => $gb->build()]);
    }

    public function handleList() {
        $arb = new AjaxRequestBuilder();
        $arb->setURL($this->link('getGrid'))
            ->setHeader(['gridPage' => '_page', 'type' => '_type'])
            ->setMethod()
            ->setFunctionName('getGrid')
            ->setFunctionArguments(['_page', '_type'])
            ->updateHTMLElement('grid-content', 'grid')
        ;

        $this->addScript($arb->build());
        $this->addScript('getGrid(0, \'pages\')');

        $cl = function(string $name, string $text) {
            return '<a class="post-data-link" href="#" onclick="handleGridFilter(\'' . $name . '\')">' . $text . '</a>';
        };

        $links = [
            $cl('pages', 'Pages'), $cl('users', 'Users')
        ];

        $this->saveToPresenterCache('links', implode('&nbsp;', $links));

        $this->addExternalScript('js/SystemCachingGridHandler.js');
    }

    public function renderList() {
        $links = $this->loadFromPresenterCache('links');

        $this->template->links = $links;
    }

    public function handleDeletePage() {
        global $app;

        $pageName = $this->httpGet('name');

        $cm = new CacheManager($app->logger);

        $files = unserialize($cm->loadCachedFiles('cachedPages'));

        $fileArray = [];
        foreach($files as $k => $v) {
            if($k == $pageName) {
                continue;
            }

            $fileArray[$k] = $files[$k];
        }

        $files = serialize($fileArray);

        $cm->saveCachedFiles('cachedPages', $files);

        $this->flashMessage('Cached page deleted.', 'success');

        $this->redirect($this->link('list'));
    }

    public function handleDeleteUser() {
        global $app;

        $userId = $this->httpGet('userId', true);

        $cm = new CacheManager($app->logger);
        $files = unserialize($cm->loadCachedFiles('users'));

        $array = [];
        foreach($files as $ue) {
            if($ue->getId() == $userId) {
                continue;
            }

            $array[] = $ue;
        }

        $array = serialize($array);

        $cm->saveCachedFiles('users', $array);

        $this->flashMessage('Cached user deleted.', 'success');

        $this->redirect($this->link('list'));
    }
}

?>