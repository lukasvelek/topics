<?php

namespace App\UI\FormBuilder;

use App\Core\HashManager;
use App\Core\Http\HttpRequest;

class FormResponse {
    private array $__keys;
    private HttpRequest $httpRequest;

    public function __construct(HttpRequest $httpRequest) {
        $this->httpRequest = $httpRequest;

        $this->__keys = [];
    }

    public function __set(string $key, mixed $value) {
        $this->__keys[$key] = $value;
    }

    public function __get(string $key) {
        return $this->__keys[$key];
    }

    public static function createFormResponseFromPostData(array $postData, HttpRequest $httpRequest) {
        $fr = new self($httpRequest);

        foreach($postData as $k => $v) {
            $fr->$k = $v;
        }

        return $fr;
    }

    public function evalBool(mixed $value1, mixed $value2) {
        return $value1 == $value2;
    }

    public function getHashedPassword(string $name = 'password') {
        if(isset($this->$name)) {
            return HashManager::hashPassword($this->$name);
        } else {
            return null;
        }
    }
}

?>