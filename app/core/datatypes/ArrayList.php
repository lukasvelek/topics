<?php

namespace App\Core\Datatypes;

/**
 * Class that represents an array but has more functionality
 * 
 * @author Lukas Velek
 */
class ArrayList {
    /**
     * All key types
     */
    private const KT_ALL = 1;

    /**
     * String key types
     */
    private const KT_STRING = 2;

    /**
     * Integer key types
     */
    private const KT_INT = 3;

    private array $_data;
    private array $_keys;
    private int $_keyType;
    private bool $_ensureKeyType;
    private bool $_allowOverwrite;

    /**
     * Class constructor
     * 
     * It initializes all variables
     */
    public function __construct() {
        $this->_data = [];
        $this->_keys = [];
        $this->_keyType = self::KT_ALL;
        $this->_ensureKeyType = false;
        $this->_allowOverwrite = true;
    }

    /**
     * Adds a value to the array
     * 
     * If key (first parameter) is null then no key is used.
     * 
     * The key must be of a certain type (or null), otherwise false is returned.
     * If _allowOverwrite is false and key already exists, then false is returned.
     * 
     * @param mixed $key Key or null (if no keys are intended to be used)
     * @param mixed $value Value
     * @return bool True on success or false on failure
     */
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

    /**
     * Updates a value with given key in array
     * 
     * If key (first parameter) is null then no key is used.
     * 
     * The key must be of a certain type, otherwise false is returned.
     * The key must already exist and _allowOverwrite must be true, otherwise false is returned.
     * 
     * @param mixed $key Key
     * @param mixed $value Value
     * @return bool True on success or false on failure
     */
    public function set(mixed $key, mixed $value) {
        if(!$this->checkKeyType($key)) {
            return false;
        }

        if(!$this->_allowOverwrite && $this->checkKeyExists($key)) {
            return false;
        }

        $this->_data[$key] = $value;

        return true;
    }

    /**
     * Checks if key exists in the array
     * 
     * @param mixed $key Key
     * @return bool True if key exists or false if not
     */
    public function keyExists(mixed $key) {
        if(!$this->checkKeyType($key)) {
            return false;
        }

        return $this->checkKeyExists($key, true);
    }

    /**
     * Returns a value from the array by its key
     * 
     * Key must be of a certain type, otherwise null is returned.
     * If the key does not exist then null is returned.
     * 
     * @param mixed Key
     * @return mixed Value or null
     */
    public function get(mixed $key) {
        if(!$this->checkKeyType($key)) {
            return null;
        }

        if(!in_array($key, $this->_keys) || !array_key_exists($key, $this->_data)) {
            return null;
        }

        return $this->_data[$key];
    }

    /**
     * Returns the whole array
     * 
     * @return array Array
     */
    public function getAll() {
        return $this->_data;
    }

    /**
     * Goes through all values and if it is a callable, then it executes it
     * 
     * @return bool True if at least once a callable was executed or false if not
     */
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

    /**
     * Resets the array to its initial state
     */
    public function reset() {
        $this->_data = [];
        $this->_keys = [];
        $this->_allowOverwrite = true;
        $this->_ensureKeyType = false;
        $this->_keyType = self::KT_ALL;
    }

    /**
     * Sets if overwriting is allowed
     * 
     * If it is allowed than values with same keys can be overwritten.
     * 
     * @param bool $allowOverwrite True if overwriting is allowed or false if not
     */
    public function setAllowOverwrite(bool $allowOverwrite = true) {
        $this->_allowOverwrite = $allowOverwrite;
    }

    /**
     * Sets if keys should have same data type
     * 
     * @param bool $ensureKeyType True if keys should have same data type
     */
    public function setEnsureKeyType(bool $ensureKeyType = false) {
        $this->_ensureKeyType = $ensureKeyType;
    }

    /**
     * Sets the key data type to string
     */
    public function setStringKeyType() {
        $this->_keyType = self::KT_STRING;
    }

    /**
     * Sets the key data type to integer
     */
    public function setIntKeyType() {
        $this->_keyType = self::KT_INT;
    }

    /**
     * Sets the key data type to mixed (everything is allowed)
     */
    public function setAllKeyType() {
        $this->_keyType = self::KT_ALL;
    }

    /**
     * Create a JSON that contains exported data
     * 
     * The exported JSON has an array with two keys - "keys" and "data". Key "keys" contains all keys used in here-defined array. Key "data" contains all data.
     * 
     * @return string JSON-encoded data
     */
    public function createDataJson() {
        $data = [
            'keys' => $this->_keys,
            'data' => $this->_data
        ];

        return json_encode($data);
    }

    /**
     * Loads data from a JSON to this array
     * 
     * The imported JSON should contain an array with key "data" that contains all data. Also it can contain key "keys" that should contain all keys used in data.
     * 
     * @param string $json JSON string
     */
    public function loadDataFromJson(string $json) {
        $this->reset();
        
        $array = json_decode($json);

        $loaded = false;

        if(array_key_exists('data', $array)) {
            $this->_data = $array['data'];
            $loaded = true;
        }

        if($loaded) {
            if(array_key_exists('keys', $array)) {
                $this->_keys = $array['keys'];
            }

            foreach($this->_data as $k => $v) {
                $this->_keys[] = $k;
            }
        }

        return $loaded;
    }

    /**
     * Checks if key has correct data type
     * 
     * @param mixed Key or null
     * @return bool True if key has correct data type or false if not
     */
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

    /**
     * Checks if key already exists in the array
     * 
     * @param mixed $key Key or null
     * @param bool $overrideOverwrite True if _allowOverwrite can be overridden
     * @return bool True if key exists (or cannot be checked) or false if not
     */
    private function checkKeyExists(mixed $key, bool $overrideOverwrite = false) {
        if(!$this->_allowOverwrite || $overrideOverwrite) {
            if(array_key_exists($key, $this->_data) && in_array($key, $this->_keys)) {
                return true;
            } else {
                return false;
            }
        }

        return true;
    }
}

?>