<?php
/**
 * экспоненциальный счётчик: каждый вызов сразу добавляет +N, а между вызовами накопленное значение затухает
 */
class MA_ExponentialCount implements MA_Interface {

    public function __construct($tau) {
        if ($tau <= 0) {
            throw new InvalidArgumentException('tau must be positive');
        }

        $this->_tau = $tau;
    }

    private function _advance($ts) {
        if ($this->_ts > 0) {
            $dt = $ts - $this->_ts;

            # debug:start
            if ($dt < 0) {
                throw new LogicException('Time went backwards');
            }
            # debug:end

            if ($dt > 0) {
                $this->_count *= exp(-$dt / $this->_tau);
            }
        }

        $this->_ts = $ts;
    }

    public function update($ts, $value = 1.0) {
        $this->_advance($ts);
        $this->_count += $value;
    }

    public function get($ts) {
        $this->_advance($ts);
        return $this->_count;
    }

    private $_tau;
    private $_ts = 0.0;
    private $_count = 0.0;
}