<?php
class MA_Sliding_Min extends MA_Sliding_Abstract {

    protected function _init() {
        return PHP_INT_MAX;
    }

    protected function _compare($prev, $actual) {
        if ($prev < $actual) {
            return $prev;
        } else {
            return $actual;
        }
    }

}