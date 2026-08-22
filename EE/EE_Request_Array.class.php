<?php
class EE_Request_Array implements EE_Request_Interface {

    public static function FromRequest(EE_Request_Interface $request) {
        // копируем аргументы
        return new self($request->getArgumentArray());
    }

    public function __construct($argumentArray = []) {
        $this->_argumentArray = $argumentArray;
    }

    public function setArgument($key, $value) {
        $this->_argumentArray[$key] = $value;
    }

    public function getArgumentArray() {
        return $this->_argumentArray;
    }

    public function getArgument($key, $type = false) {
        // @todo общий код с Cli
        if (isset($this->_argumentArray[$key])) {
            $x = $this->_argumentArray[$key];

            // опциональная типизация
            if ($type) {
                return EE_Typing::TypeValue($x, $type);
            } else {
                return $x;
            }
        } else {
            throw new EE_Exception('No argument ' . $key);
        }
    }

    public function getArgumentSecure($key, $type = false) {
        // @todo можно вынести в Request_Abstract
        try {
            return $this->getArgument($key, $type);
        } catch (Exception) {
            if ($type) {
                return EE_Typing::TypeValue(false, $type);
            } else {
                return false;
            }
        }
    }

    public function hasArgument($key) {
        return isset($this->_argumentArray[$key]);
    }

    private $_argumentArray = [];

}