<?php
class StreamLoop_GC extends StreamLoop_Timer_Abstract {

    public function __construct(StreamLoop $loop) {
        parent::__construct(
            $loop,
            100, // special timer ID for GC, будет -100 внутри
            3600 // every hour
        );
    }

    protected function _onTimer($tsSelect) {
        gc_collect_cycles(); // 72 ns/call
    }

}