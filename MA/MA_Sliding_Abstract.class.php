<?php
abstract class MA_Sliding_Abstract implements MA_Interface {

    abstract protected function _init();
    abstract protected function _compare($prev, $actual);

    public function __construct($window) {
        if ($window <= 0) {
            throw new InvalidArgumentException(
                'Window must be greater than 0'
            );
        }

        $this->_window = $window;
        $this->_valueLast = $this->_init();
    }

    private function _advance($ts) {
        # debug:start
        if ($this->_tsLast !== null && $ts < $this->_tsLast) {
            throw new LogicException('SlidingPrev time travel');
        }
        # debug:end

        $this->_tsLast = $ts;

        $tsFrom = $ts - $this->_window;

        foreach ($this->_valueArray as $index => [$tsEnd, $prev]) {
            if ($tsEnd > $tsFrom) {
                // Остальные записи тоже ещё актуальны:
                // время в массиве идёт по возрастанию.
                break;
            }

            unset($this->_valueArray[$index]);
        }
    }

    public function update($ts, $value) {
        $this->_advance($ts);

        // Старое значение действовало до ts.
        $this->_valueArray[] = [$ts, $this->_valueLast];

        $this->_valueLast = $value;
    }

    public function get($ts) {
        $this->_advance($ts);

        $result = $this->_valueLast;

        foreach ($this->_valueArray as [$tsEnd, $prev]) {
            $result = $this->_compare($prev, $result);
        }

        return $result;
    }

    private $_window;
    private $_valueArray = [];
    private $_valueLast;
    private $_tsLast = null;

}