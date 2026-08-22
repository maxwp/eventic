<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */
/**
 * Abstract content
 */
abstract class EE_Content_Abstract implements EE_Content_Interface {

    public function main(EE_Request_Interface $request) {
        $this->_request = $request;

        $this->process();

        return $this->_valueArray;
    }

    abstract public function process(); // вызывается из main, для обратной совместимости со всем

    /**
     * @return EE_Request_Interface
     */
    public function getRequest() {
        return $this->_request;
    }

    /**
     * Получить входящий аргумент
     * Если аргумента нет - будет EE_Exception
     *
     * @param string $key
     * @param mixed $type
     *
     * @return mixed
     */
    public function getArgument($key, $type = false) {
        return $this->_request->getArgument($key, $type);
    }

    /**
     * Безопасно получить аргумент.
     * Если аргумента нет - будет false.
     *
     * @param string $key
     * @param mixed $type
     *
     * @return mixed
     * @see getArgument()
     *
     */
    public function getArgumentSecure($key, $type = false) {
        return $this->_request->getArgumentSecure($key, $type);
    }

    /**
     * Получить все аргументы
     *
     * @return array
     */
    public function getArgumentArray() {
        return $this->_request->getArgumentArray();
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function setValue($key, $value) {
        if ($key) {
            $this->_valueArray[$key] = $value;
        } else {
            throw new EE_Exception("Empty key name. Nothing to set");
        }
    }

    public function updateValueArray($a) {
        $this->_valueArray += $a;
    }

    public function setValueArray($a) {
        $this->_valueArray = $a;
    }

    public function unsetValue($key) {
        unset($this->_valueArray[$key]);
    }

    public function getValue($key) {
        if (isset($this->_valueArray[$key])) {
            return $this->_valueArray[$key];
        }

        if (!$key) {
            throw new EE_Exception('Empty key name');
        }

        return false;
    }

    /**
     * Добавить значений в контент (массово)
     *
     * @param array $a
     */
    public function addValueArray($a) {
        if (!$this->_valueArray) {
            $this->_valueArray = $a;
        } else {
            $this->_valueArray = array_merge($this->_valueArray, $a);
        }
    }

    /**
     * Получить все установленные поля
     * 2D-array {key => value}
     *
     * @return array
     */
    public function getValueArray() {
        return $this->_valueArray;
    }

    private EE_Request_Interface $_request;
    private $_valueArray = [];

}