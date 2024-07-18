<?php

namespace App\UI\GridBuilder;

use App\Core\AjaxRequestBuilder;
use App\Modules\APresenter;

class AjaxGridHelper {
    private int $page;
    private string $name;
    private array $customURLParams;
    private ?APresenter $presenter;
    private string $htmlElementId;

    public function __construct(APresenter $presenter) {
        $this->page = 0;
        $this->name = 'myGrid';
        $this->customURLParams = [];
        $this->presenter = $presenter;
        $this->htmlElementId = 'grid-content';
        return $this;
    }

    public function setHtmlElementID(string $htmlElementId) {
        $this->htmlElementId = $htmlElementId;
    }

    public function setPage(int $page) {
        if($page < 0) {
            $page = 0;
        }
        $this->page = $page;
        return $this;
    }

    public function setName(string $name) {
        $this->name = $name;
        return $this;
    }

    public function setCustomURLParams(array $customURLParams) {
        $this->customURLParams = $customURLParams;
        return $this;
    }

    public function call(string $action, array $params = []) {
        $arb = $this->createAjaxRequest($action);

        $this->presenter->addScript($arb->build());

        $args = array_merge([$this->page], $params);

        $this->presenter->addScript($this->name . '("' . implode('", "', $args) . '");');
    }

    private function createAjaxRequest(string $action) {
        $params = ['_page'];
        foreach($this->customURLParams as $k => $v) {
            $params[] = '_' . $k;
        }

        $url = $this->presenter->link($action);

        $arb = new AjaxRequestBuilder();
        $arb->setURL($url)
            ->setMethod()
            ->setHeader(['gridPage' => $this->page])
            ->setFunctionName($this->name)
            ->setFunctionArguments($params)
            ->updateHTMLElement($this->htmlElementId, 'grid')
        ;

        return $arb;
    }

    public static function getCommonGridFactory(APresenter $presenter, string $name, string $action, array $customURLParams = []) {
        $obj = new self($presenter);
        $obj->setName($name)
            ->setCustomURLParams($customURLParams)
            ->call($action);
    }
}

?>