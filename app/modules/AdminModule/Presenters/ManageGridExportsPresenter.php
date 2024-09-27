<?php

namespace App\Modules\AdminModule;

use App\Core\AjaxRequestBuilder;
use App\Entities\GridExportEntity;
use App\Helpers\DateTimeFormatHelper;
use App\Helpers\GridHelper;
use App\UI\GridBuilder\Cell;
use App\UI\GridBuilder\DefaultGridReducer;
use App\UI\GridBuilder\GridBuilder;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class ManageGridExportsPresenter extends AAdminPresenter {
    private GridHelper $gridHelper;

    public function __construct() {
        parent::__construct('ManageGridExportsPresenter', 'Manage grid exports');
    }

    public function startup() {
        parent::startup();
        
        $this->gridHelper = new GridHelper($this->logger, $this->getUserId());
    }

    public function handleList() {
        $filter = $this->httpGet('filter');

        if($filter === null) {
            $filter = 'all';
        }

        $activeAll = ($filter == 'all');

        $links = [
            ($activeAll ? '<b>' : '') . LinkBuilder::createSimpleLink('All', $this->createURL('list', ['filter' => 'all']), 'post-data-link') . ($activeAll ? '</b>' : ''),
            ($activeAll ? '' : '<b>') . LinkBuilder::createSimpleLink('My', $this->createURL('list', ['filter' => 'my']), 'post-data-link') . ($activeAll ? '' : '</b>')
        ];

        $this->saveToPresenterCache('links', implode('&nbsp;&nbsp;', $links));

        $arb = new AjaxRequestBuilder();

        $arb->setMethod()
            ->setAction($this, 'getGrid')
            ->setHeader(['gridPage' => '_page', 'gridFilter' => '_filter'])
            ->setFunctionName('getGrid')
            ->setFunctionArguments(['_page', '_filter'])
            ->updateHTMLElement('grid-content', 'grid')
        ;

        $this->addScript($arb);
        $this->addScript('getGrid(0, \'' . $filter . '\')');
    }

    public function renderList() {
        $this->template->links = $this->loadFromPresenterCache('links');
    }

    public function actionGetGrid() {
        $gridPage = $this->httpGet('gridPage', true);
        $filter = $this->httpGet('gridFilter', true);

        $gridSize = $this->app->getGridSize();

        $page = $this->gridHelper->getGridPage(GridHelper::GRID_GRID_EXPORTS, $gridPage, [$filter]);

        $exports = [];
        $totalCount = 0;
        if($filter == 'all') {
            $exports = $this->app->gridExportRepository->getExportsForGrid($gridSize, ($page * $gridSize));
            $totalCount = count($this->app->gridExportRepository->getExportsForGrid(0, 0));
        } else {
            $exports = $this->app->gridExportRepository->getUserExportsForGrid($this->getUserId(), $gridSize, ($page * $gridSize));
            $totalCount = count($this->app->gridExportRepository->getUserExportsForGrid($this->getUserId(), 0, 0));
        }

        $lastPage = ceil($totalCount / $gridSize);

        $gb = new GridBuilder();

        $gb->setIdElement('gridbuilder-grid');

        $columns = [
            'gridName' => 'Grid',
            'dateCreated' => 'Date created',
            'dateFinished' => 'Date finished',
            'entryCount' => 'Count'
        ];

        if($filter == 'all') {
            $columns = array_merge(['userId' => 'User'], $columns);
        }

        $gb->addColumns($columns);
        $gb->addDataSource($exports);
        
        $gd = new DefaultGridReducer($this->app->userRepository, $this->app->topicRepository, $this->app->postRepository);
        $gd->applyReducer($gb);

        $gb->addOnColumnRender('dateCreated', function(Cell $cell, GridExportEntity $gee) {
            $cell->setValue(DateTimeFormatHelper::formatDateToUserFriendly($gee->getDateCreated()));
            $cell->setTitle(DateTimeFormatHelper::formatDateToUserFriendly($gee->getDateCreated(), DateTimeFormatHelper::ATOM_FORMAT));
            return $cell;
        });
        $gb->addOnColumnRender('dateFinished', function(Cell $cell, GridExportEntity $gee) {
            $cell->setValue(DateTimeFormatHelper::formatDateToUserFriendly($gee->getDateFinished()));
            $cell->setTitle(DateTimeFormatHelper::formatDateToUserFriendly($gee->getDateFinished(), DateTimeFormatHelper::ATOM_FORMAT));
            return $cell;
        });

        $gb->addAction(function(GridExportEntity $gee) {
            if($gee->getFilename() !== null) {
                $lb = new LinkBuilder();
                $lb->setHref($gee->getFilename())
                    ->setClass('grid-link')
                    ->setText('Download')
                ;

                return $lb->render();
            } else {
                return '-';
            }
        });

        $gb->addGridPaging($page, $lastPage, $gridSize, $totalCount, 'getGrid', [$filter]);
        
        $gb->addGridExport(function() use ($filter) {
            if($filter == 'all') {
                return $this->app->gridExportRepository->getExportsForGrid(0, 0);
            } else {
                return $this->app->gridExportRepository->getUserExportsForGrid($this->getUserId(), 0, 0);
            }
        }, GridHelper::GRID_GRID_EXPORTS, $this->logger);

        return ['grid' => $gb->build()];
    }
}

?>