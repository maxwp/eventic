<?php
class MA_ContinuousEMA implements MA_Interface {

    /**
     * Важное объяснение по tau:
     * tau = 40 ms / ln(2) => через 40 ms останется 50% палки (half-life = 40 ms)
     * tau = 40 ms / 3 => через 40 ms останется около 5% палки (≈95% забудется)
     *
     * @param $tau
     * @param $initEMA
     */
    public function __construct($tau, $initEMA) {
        if ($tau <= 0.0) {
            throw new InvalidArgumentException('Tau must be positive');
        }

        $this->_tau = $tau;

        // я сразу задаю EMA, чтобы не гонять дальше бесконечные проверки if null
        $this->_ema = $initEMA;
        $this->_value = $initEMA;
        $this->_ts = 1.0; // стартовое время далеко в прошлом
    }

    /**
     * Протянуть EMA до указанного времени по текущему value.
     */
    private function _advance($ts) {
        # debug:start
        if ($ts < $this->_ts) {
            throw new LogicException('MA time travel');
        }
        # debug:end

        $dt = $ts - $this->_ts;
        if ($dt > 0) { // один if проще чем куча математики
            $this->_ema += (1.0 - exp(-$dt / $this->_tau)) * ($this->_value - $this->_ema);
        }

        $this->_ts = $ts;
    }

    /**
     * Новое значение начинает действовать с ts.
     */
    public function update($ts, $value) {
        // дотягиваем ema до этой точки
        $this->_advance($ts); // @todo inline for performance

        // новое значение НЕ двигает EMA мгновенно, просто запоминаю
        $this->_value = $value;
    }

    /**
     * Получить EMA на момент ts
     */
    public function get($ts) {
        // дотягиваем ema до этой точки
        $this->_advance($ts); // @todo inline for performance

        return $this->_ema;
    }

    private $_tau; // float
    private $_ema = 0.0; // float
    private $_value = 0.0; // float
    private $_ts = 0.0; // float

}