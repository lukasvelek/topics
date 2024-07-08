<?php

namespace App\Components\Grid;

use App\Core\AjaxRequestBuilder;

class Grid {
    private ?string $gridElementId;
    private ?string $gridPaginatorElementId;
    private array $gridHandlerLink;
    private string $name;
    private array $customArgs;
    private ?string $ajaxGridResultElement;
    private ?string $ajaxGridPaginatorResultElement;
    private ?string $ajaxFunctionName;

    public function __construct() {
        $this->gridElementId = 'grid';
        $this->gridPaginatorElementId = 'grid-paginator';
        $this->gridHandlerLink = [];
        $this->name = 'MyGrid';
        $this->customArgs = [];
        $this->ajaxGridResultElement = 'grid';
        $this->ajaxGridPaginatorResultElement = 'paginator';
        $this->ajaxFunctionName = null;
    }

    public function setGridElementId(string $gridElementId) {
        $this->gridElementId = $gridElementId;
    }

    public function setGridPaginatorElementId(string $gridPaginatorElementId) {
        $this->gridPaginatorElementId = $gridPaginatorElementId;
    }

    public function setGridHandlerLink(array $url) {
        $this->gridHandlerLink = $url;
    }

    public function setName(string $name) {
        $this->name = ucfirst($name);
    }

    /*public function setCustomArgs(array $args) {
        $this->customArgs = $args;
    }*/

    public function setAjaxGridResultElementName(string $name) {
        $this->ajaxGridResultElement = $name;
    }

    public function setAjaxGridPaginatorResultElementName(string $name) {
        $this->ajaxGridPaginatorResultElement = $name;
    }

    public function getAjaxRequestCode() {
        $headerArgs = [
            'gridPage' => '_page',
            'topicId' => '_topicId'
        ];

        $funcArgs = [
            '_page',
            '_topicId'
        ];

        /*foreach($this->customArgs as $php => $js) {
            $headerArgs[$php] = $js;
            $funcArgs[] = $js;
        }*/

        $funcName = 'get' . $this->name;
        $this->ajaxFunctionName = $funcName;

        $arb = new AjaxRequestBuilder();

        $arb->setURL($this->gridHandlerLink)
            ->setMethod()
            ->setHeader($headerArgs)
            ->setFunctionName($funcName)
            ->setFunctionArguments($funcArgs)
            ->updateHTMLElement($this->gridElementId, $this->ajaxGridResultElement)
            ->updateHTMLElement($this->gridPaginatorElementId, $this->ajaxGridPaginatorResultElement);

        return $arb->build();
    }

    public function call() {
        
    }
}

?>