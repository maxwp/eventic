<?php
class Benchmark_math implements Benchmark_Interface {

    public function process() {
        $a = rand(1, 100);
        $b = rand(1, 100);

        $alpha = 2 / ($this->_n + 1);
        $this->_ema += $alpha * (log($a / $b) - $this->_ema);
    }

    private $_ema = 0;
    private $_n = 100;

}