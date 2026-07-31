<?php
class Benchmark_call implements Benchmark_Interface {

    public function process() {
        // 26:
        $int = rand(0, 1);
        $bool = (bool)$int;

        // 45 - 26
        $this->_callIf($bool);
    }

    private function _callIf($x) {
        if ($x) {

        } else {

        }
    }

}