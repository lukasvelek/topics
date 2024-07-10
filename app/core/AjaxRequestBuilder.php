<?php

namespace App\Core;

use App\Modules\APresenter;

class AjaxRequestBuilder {
    private ?string $url;
    private array $headerParams;
    private array $whenDoneOperations;
    private ?string $functionName;
    private ?string $method;
    private array $functionArgs;
    private array $beforeAjaxOperations;
    private array $customArgs;
    private array $elements;
    private bool $useLoadingAnimation;

    public function __construct() {
        $this->url = null;
        $this->headerParams = [];
        $this->whenDoneOperations = [];
        $this->functionName = null;
        $this->method = null;
        $this->functionArgs = [];
        $this->beforeAjaxOperations = [];
        $this->customArgs = [];
        $this->elements = [];
        $this->useLoadingAnimation = true;

        return $this;
    }
    
    public function disableLoadingAnimation() {
        $this->useLoadingAnimation = false;

        return $this;
    }

    public function addBeforeAjaxOperation(string $code) {
        $this->beforeAjaxOperations[] = $code;

        return $this;
    }

    public function addCustomArg(string $argName) {
        $this->customArgs[] = $argName;

        return $this;
    }

    public function setAction(APresenter $presenter, string $actionName) {
        $module = $presenter->moduleName;
        $presenter = $presenter->getCleanName();

        $this->url = $this->composeURLFromArray(['page' => $module . ':' . $presenter, 'action' => $actionName]);

        return $this;
    }

    public function setURL(array $url) {
        $this->url = $this->composeURLFromArray($url);

        return $this;
    }

    private function composeURLFromArray(array $url) {
        global $app;

        return $app->composeURL($url);
    }

    public function setHeader(array $params) {
        $this->headerParams = $params;

        return $this;
    }

    public function addWhenDoneOperation(string $code) {
        $this->whenDoneOperations[] = $code;

        return $this;
    }

    public function updateHTMLElement(string $htmlElementId, string $jsonResultName, bool $append = false) {
        if(!$append) {
            $this->elements[] = $htmlElementId;
        }
        $this->addWhenDoneOperation('$("#' . $htmlElementId . '").' . ($append ? 'append' : 'html') . '(obj.' . $jsonResultName . ');');

        return $this;
    }

    public function updateHTMLElementRaw(string $htmlElementId, string $jsonResultName, bool $append = false) {
        if(!$append) {
            $this->elements[] = $htmlElementId;
        }
        $this->addWhenDoneOperation('$(' . $htmlElementId . ').' . ($append ? 'append' : 'html') . '(obj.' . $jsonResultName . ');');

        return $this;
    }

    public function hideHTMLElementRaw(string $htmlElementId) {
        $this->addWhenDoneOperation('$(' . $htmlElementId . ').hide();');

        return $this;
    }

    public function hideHTMLElement(string $htmlElementId) {
        $this->hideHTMLElementRaw('"#' . $htmlElementId . '"');

        return $this;
    }

    public function addCustomWhenDoneCode(string $code) {
        $this->addWhenDoneOperation($code);

        return $this;
    }

    public function setFunctionName(string $name) {
        $this->functionName = $name;

        return $this;
    }

    public function setFunctionArguments(array $args) {
        $this->functionArgs = $args;

        return $this;
    }

    public function setMethod(string $method = 'GET') {
        $this->method = $method;

        return $this;
    }

    public function build() {
        if(!$this->checkParameters()) {
            return null;
        }
        
        if($this->useLoadingAnimation) {
            $this->createLoadingAnimation();
        }

        $headParams = $this->processHeadParams();

        $code = [];

        $code[] = 'async function ' . $this->functionName . '(' . implode(', ', $this->functionArgs) . ') {';

        if(!empty($this->beforeAjaxOperations)) {
            foreach($this->beforeAjaxOperations as $bao) {
                $code[] = $bao;
            }
        }

        if(strtoupper($this->method) == 'GET') {
            $code[] = '$.get(';
            $code[] = '"' . $this->url . '",';
            $code[] = '' . $headParams . '';
            $code[] = ')';
        }
        
        if(!empty($this->whenDoneOperations)) {
            $code[] = '.done(function(data){';
            $code[] = 'const obj = JSON.parse(data);';

            foreach($this->whenDoneOperations as $wdo) {
                $code[] = $wdo;
            }

            $code[] = '});';
        }

        $code[] = '}';

        return implode('', $code);
    }

    private function processHeadParams() {
        if(!array_key_exists('isAjax', $this->headerParams)) {
            $this->headerParams['isAjax'] = 1;
        }

        $json = json_encode($this->headerParams);

        foreach($this->functionArgs as $fa) {
            if(str_contains($json, '"' . $fa . '"')) {
                $json = str_replace('"'. $fa . '"', $fa, $json);
            }
        }

        foreach($this->customArgs as $ca) {
            if(str_contains($json, '"' . $ca . '"')) {
                $json = str_replace('"' . $ca . '"', $ca, $json);
            }
        }

        return $json;
    }

    private function checkParameters() {
        if($this->functionName === null) {
            return false;
        }
        if($this->method === null || (strtoupper($this->method) != 'GET' && strtoupper($this->method) != 'POST')) {
            return false;
        }
        if($this->url === null) {
            return false;
        }

        return true;
    }

    private function createLoadingAnimation() {
        foreach($this->elements as $element) {
            if($element == 'grid-content') {
                $code = '
                    $("#' . $element . '").html(\'<div id="center"><img src="resources/loading.gif" width="64"><br>Loading...</div>\');
                ';

                $this->addBeforeAjaxOperation($code);
            }
        }

        $this->addBeforeAjaxOperation('await sleep(100);');
    }
}

?>