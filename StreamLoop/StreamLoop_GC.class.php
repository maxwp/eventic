<?php
class StreamLoop_GC extends StreamLoop_Timer_Abstract {

    public function __construct(StreamLoop $loop) {
        $timerID = 100; // special timer ID for GC, будет -100 внутри
        $timeout = 3600; // every hour

        parent::__construct($loop, $timerID, $timeout);
    }

    protected function _onTimer($tsSelect) {
        gc_collect_cycles(); // 72 ns/call
    }

}