<?php
/**
 * Обычная EMA где alpha = 2 / (period + 1)
 */
class MA_Exponential implements MA_Interface {

    /**
     * @param $period
     * @param $initEMA
     */
    public function __construct($period, $initEMA) {
        if ($period <= 0) {
            throw new InvalidArgumentException('Period must be int positive');
        }

        $this->_alpha = 2 /  ($period + 1);

        // я сразу задаю EMA, чтобы не гонять дальше бесконечные проверки if null
        $this->_ema = $initEMA;
        $this->_ts = 1.0; // стартовое время далеко в прошлом
    }

    /**
     * Новое значение начинает действовать с ts.
     */
    public function update($ts, $value) {
        # debug:start
        if ($ts < $this->_ts) {
            throw new Exception(__CLASS__.' ts time walk');
        }
        # debug:end

        $this->_ema += $this->_alpha * ($value - $this->_ema);

        $this->_ts = $ts;

        return $this->_ema;
    }

    public function get($ts) {
        return $this->_ema;
    }

    private $_alpha; // float
    private $_ema = 0.0; // float
    private $_ts = 0.0; // float

}