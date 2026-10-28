<?php
class MA_DelayedValue implements MA_Interface {

    // @todo возможно без SPL

    public function __construct($delay, $initValue) {
        if ($delay < 0) {
            throw new InvalidArgumentException(
                'Delay must be non-negative'
            );
        }

        $this->_delay = $delay;
        $this->_value = $initValue;
        $this->_queue = new SplQueue();
    }

    private function _advance($ts) {
        # debug:start
        if ($this->_ts !== null && $ts < $this->_ts) {
            throw new LogicException('MA time travel');
        }
        # debug:end

        $this->_ts = $ts;

        $target = $ts - $this->_delay;

        while (!$this->_queue->isEmpty()) {
            [$pointTs, $pointValue] = $this->_queue->bottom();

            if ($pointTs > $target) {
                break;
            }

            $this->_queue->dequeue();
            $this->_value = $pointValue;
        }
    }

    public function update($ts, $value) {
        $this->_advance($ts);

        $this->_queue->enqueue([$ts, $value]);
    }

    public function get($ts) {
        $this->_advance($ts);

        return $this->_value;
    }

    private $_delay;
    private $_value;
    private $_queue;
    private $_ts = null;

}