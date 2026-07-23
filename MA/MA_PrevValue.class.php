<?php
class MA_PrevValue implements MA_Interface {

    public function __construct() {
        $this->_ma = 1.0;
        $this->_value = 1.0;
        $this->_ts = 1.0; // стартовое время далеко в прошлом
    }

    private function _advance($ts) {
        # debug:start
        if ($ts < $this->_ts) {
            throw new LogicException('MA time travel');
        }
        # debug:end

        if ($ts > $this->_ts) {
            $this->_ma = $this->_value;
        }

        $this->_ts = $ts;
    }

    public function update($ts, $value) {
        $this->_advance($ts); // дотягиваем ema до этой точки
        $this->_value = $value;
    }

    public function get($ts) {
        $this->_advance($ts); // дотягиваем  до этой точки
        return $this->_ma;
    }

    private $_ma = 0.0; // float
    private $_value = 0.0; // float
    private $_ts = 0.0; // float

}