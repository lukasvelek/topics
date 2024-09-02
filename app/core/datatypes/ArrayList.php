<?php

namespace App\Core\Datatypes;

class ArrayList {
    private const KT_ALL = 1;
    private const KT_STRING = 2;
    private const KT_INT = 3;

    private array $_data;
    private array $_keys;
    private int $_keyType;
    private bool $_ensureKeyType;
    private bool $_allowOverwrite;

    public function __construct() {
        $this->_data = [];
        $this->_keys = [];
        $this->_keyType = self::KT_ALL;
        $this->_ensureKeyType = false;
        $this->_allowOverwrite = true;
    }

    public function add(mixed $key, mixed $value) {
        if(!$this->checkKeyType($key)) {
            return false;
        }

        if($this->checkKeyExists($key)) {
            return false;
        }

        if($key !== null) {
            $this->_keys[] = $key;
            $this->_data[$key] = $value;
        } else {
            $this->_data[] = $value;
        }

        return true;
    }

    public function set(mixed $key, mixed $value) {
        if(!$this->checkKeyType($key)) {
            return false;
        }

        if(!$this->_allowOverwrite && $this->checkKeyExists($key)) {
            return false;
        }

        if(!in_array($key, $this->_keys) && !array_key_exists($key, $this->_data)) {
            return $this->add($key, $value);
        }

        $this->_data[$key] = $value;

        return true;
    }

    public function keyExists(mixed $key) {
        if(!$this->checkKeyType($key)) {
            return false;
        }

        return $this->checkKeyExists($key, true);
    }

    public function get(mixed $key) {
        if(!$this->checkKeyType($key)) {
            return null;
        }

        if($this->checkKeyExists($key)) {
            return null;
        }

        return $this->_data[$key];
    }

    public function getAll() {
        return $this->_data;
    }

    public function executeCallables() {
        $executed = false;

        foreach($this->_data as $d) {
            if(is_callable($d)) {
                $executed = true;
                $d();
            }
        }

        return $executed;
    }

    public function reset() {
        $this->_data = [];
        $this->_keys = [];
        $this->_allowOverwrite = true;
        $this->_ensureKeyType = false;
        $this->_keyType = self::KT_ALL;
    }

    public function setAllowOverwrite(bool $allowOverwrite = true) {
        $this->_allowOverwrite = $allowOverwrite;
    }

    public function setEnsureKeyType(bool $ensureKeyType = false) {
        $this->_ensureKeyType = $ensureKeyType;
    }

    public function setStringKeyType() {
        $this->_keyType = self::KT_STRING;
    }

    public function setIntKeyType() {
        $this->_keyType = self::KT_INT;
    }

    public function setAllKeyType() {
        $this->_keyType = self::KT_ALL;
    }

    public function createJson() {
        $data = $this->internalCreateDataArrayForJson();

        return json_encode($data);
    }

    public function loadDataFromJson(string $json) {
        $this->reset();
        
        $array = json_decode($json);

        if(array_key_exists('keys', $array)) {
            $this->_keys = $array['keys'];
        }
        
        if(array_key_exists('data', $array)) {
            $this->_data = $array['data'];
        }
    }

    public function exportAsJson() {
        $data = $this->internalCreateDataArrayForJson();

        $data['overwrite'] = $this->_allowOverwrite;
        $data['ensureKeyType'] = $this->_ensureKeyType;
        $data['keyType'] = $this->_keyType;

        return json_encode($data);
    }

    private function internalCreateDataArrayForJson() {
        return [
            'keys' => $this->_keys,
            'data' => $this->_data
        ];
    }

    private function checkKeyType(mixed $key) {
        if($this->_ensureKeyType) {
            switch($this->_keyType) {
                case self::KT_STRING:
                    if(!is_string($key)) {
                        return false;
                    }
                    
                    break;
                
                case self::KT_INT:
                    if(!is_numeric($key) && !is_integer($key)) {
                        return false;
                    }

                    break;
            }
        }

        return true;
    }

    private function checkKeyExists(mixed $key, bool $overrideOverwrite = false) {
        if(!$this->_allowOverwrite || $overrideOverwrite) {
            if(array_key_exists($key, $this->_data) && in_array($key, $this->_keys)) {
                return true;
            }
        }

        return false;
    }

    public static function createFromJson(string $json) {
        $array = json_decode($json);

        $al = new self();

        if(array_key_exists('keys', $array)) {
            $al->_keys = $array['keys'];
        }
        
        if(array_key_exists('data', $array)) {
            $al->_data = $array['data'];
        }

        if(array_key_exists('overwrite', $array)) {
            $al->_allowOverwrite = in_array($array['overwrite'], ['1', 'true']);
        }

        if(array_key_exists('ensureKeyType', $array)) {
            $al->_ensureKeyType = in_array($array['ensureKeyType'], ['1', 'true']);
        }

        if(array_key_exists('keyType', $array)) {
            $al->_keyType = $array['keyType'];
        }

        return $al;
    }
}

?>