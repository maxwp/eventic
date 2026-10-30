<?php
class MA_Sliding_Count implements MA_Interface {

    public function __construct($window) {
        if ($window <= 0.0) {
            throw new InvalidArgumentException('Window must be positive');
        }

        $this->_window = $window;
        $this->_queue = new SplQueue();
    }

    private function _advance($ts) {
        # debug:start
        if ($this->_ts !== null && $ts < $this->_ts) {
            throw new LogicException("MA time travel $ts < {$this->_ts}");
        }
        # debug:end

        $cutoff = $ts - $this->_window;

        while (!$this->_queue->isEmpty()) {
            $event = $this->_queue->bottom();

            if ($event[0] > $cutoff) {
                break;
            }

            $this->_queue->dequeue();
            $this->_count -= $event[1];
        }

        // Убираем возможный остаток от арифметики float.
        if ($this->_queue->isEmpty()) {
            $this->_count = 0.0;
        }

        $this->_ts = $ts;
    }

    /**
     * Добавить value в счётчик на момент ts.
     * Каждый update учитывается, включая одинаковые ts.
     */
    public function update($ts, $value = 1.0) {
        $this->_advance($ts);

        $this->_queue->enqueue([$ts, $value]);
        $this->_count += $value;
    }

    /**
     * Получить сумму value в окне (ts - window, ts].
     * Устаревшие события удаляются и без новых update.
     */
    public function get($ts) {
        $this->_advance($ts);

        return $this->_count;
    }

    private $_window;
    private $_queue;
    private $_count = 0.0;
    private $_ts = null;

}