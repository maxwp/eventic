<?php
abstract class StreamLoop_Timer_Abstract extends StreamLoop_Handler_Abstract {

    // @todo dynamic timeout option

    abstract protected function _onTimer($tsSelect);

    /**
     *  Важно: таймер может сработать не супер точно, а с дрейфом на время обработки handler-ов.
     *  Это связано с тем, что я использую prev_tsSelect для расчета таймеров следуюего круга.
     *  Потому что запрос времени занимает 40 ns, и это реально 1/3 от всего event loop'a.
     *
     * @param StreamLoop $loop
     * @param $timerID
     * @param $timeout
     * @throws StreamLoop_Exception
     */
    public function __construct(StreamLoop $loop, $timerID, $timeout) {
        parent::__construct($loop);

        $timeout = (float) $timeout;
        if ($timeout <= 0) {
            throw new StreamLoop_Exception('Timeout must be a positive number');
        }
        $this->_timeout = $timeout;

        // @todo how to check id? = registry of SL timers
        $this->streamID = -1 * (int) $timerID; // id нужен отрицательный чтобы не пересекся с настоящими stream
        $this->stream = null;

        $this->_loop->registerHandler($this); // 1st register
        // $this->_loop->updateHandlerFlags($this, false, false); // даже вызывать не надо
        $this->_loop->updateStreamTimeout($this->streamID, microtime(true) + $timeout);
    }

    public function readyRead($tsSelect) {
        // nothing
    }

    public function readyWrite($tsSelect) {
        // nothing
    }

    public function readyTimeout($tsSelect) {
        // сначала меняем handler, а затем вызываем onTimer
        $this->_loop->updateStreamTimeout($this->streamID, $tsSelect + $this->_timeout); // readyTimeout
        $this->_onTimer($tsSelect);
    }

    private $_timeout = 0.0; // float

}