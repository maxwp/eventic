<?php
class Benchmark_microtime implements Benchmark_Interface {

    public function process() {
        $x = microtime(true);
    }

}