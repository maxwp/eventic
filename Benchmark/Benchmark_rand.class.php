<?php
class Benchmark_rand implements Benchmark_Interface {

    public function process() {
        $x = rand();
    }

}