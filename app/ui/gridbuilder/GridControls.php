<?php

namespace App\UI\GridBuilder;

use App\UI\IRenderable;

/**
 * @deprecated
 */
class GridControls implements IRenderable {
    private ?string $gridPagingInfo;
    private ?string $gridRefresh;
    private ?string $gridControls;
    private ?string $gridExport;

    public function __construct() {
        $this->gridPagingInfo = null;
        $this->gridRefresh = null;
        $this->gridControls = null;
        $this->gridExport = null;
    }

    public function setGridPagingInfo(string $gridPagingInfo) {
        $this->gridPagingInfo = $gridPagingInfo;
    }

    public function setGridRefresh(string $gridRefresh) {
        $this->gridRefresh = $gridRefresh;
    }

    public function setGridControls(string $gridControls) {
        $this->gridControls = $gridControls;
    }

    public function setGridExport(string $gridExport) {
        $this->gridExport = $gridExport;
    }

    public function render() {
        $gridPagingInfoSection = '';

        if($this->gridPagingInfo !== null) {
            $gridPagingInfoSection = '<div class="col-md">' . $this->gridPagingInfo . '</div>';
        }

        $gridRefreshSection = '';

        if($this->gridRefresh !== null) {
            $gridRefreshSection = '<div class="col-md">' . $this->gridRefresh . '</div>';
        }

        $gridExportSection = '';

        if($this->gridExport !== null) {
            $gridExportSection = '<div class="col-md">' . $this->gridExport . '</div>';
        }

        $gridControlsSection = '';
        
        if($this->gridControls !== null) {
            $gridControlsSection = '<div class="col-md" id="right">' . $this->gridControls . '</div>';
        }

        $code = '
            <div class="row">
                ' . $gridPagingInfoSection . '
                ' . $gridRefreshSection . '
                ' . $gridExportSection . '
                ' . $gridControlsSection . '
            </div>
        ';

        return $code;
    }
}

?>