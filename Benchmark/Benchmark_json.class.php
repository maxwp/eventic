<?php
class Benchmark_json implements Benchmark_Interface {

    public function process() {
        // 60 ns
        $payload = '{"e":"bookTicker","u":10055087633111,"s":"XMRUSDT","b":"345.23","B":"0.625","a":"345.24","A":"0.064","T":' . rand() . ',"E":1772812163287}';

        // 428 - 60 = 368
        $data = simdjson_decode($payload, true);
    }

}