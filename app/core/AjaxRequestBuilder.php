<?php

namespace App\Core;

class AjaxRequestBuilder {
    private ?string $url;
    private array $headerParams;
    private array $whenDoneOperations;
    private ?string $functionName;
    private ?string $method;
    private array $functionArgs;

    public function __construct() {
        $this->url = null;
        $this->headerParams = [];
        $this->whenDoneOperations = [];
        $this->functionName = null;
        $this->method = null;
        $this->functionArgs = [];

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
        $this->addWhenDoneOperation('$("#' . $htmlElementId . '").' . ($append ? 'append' : 'html') . '(obj.' . $jsonResultName . ');');

        return $this;
    }

    public function updateHTMLElementRaw(string $htmlElementId, string $jsonResultName, bool $append = false) {
        $this->addWhenDoneOperation('$(' . $htmlElementId . ').' . ($append ? 'append' : 'html') . '(obj.' . $jsonResultName . ');');

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

        $headParams = $this->processHeadParams();

        $code = [];

        $code[] = 'function ' . $this->functionName . '(' . implode(', ', $this->functionArgs) . ') {';

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
}

?>