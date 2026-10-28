<?php
class MA_Sliding_Max extends MA_Sliding_Abstract {

    protected function _init() {
        return PHP_INT_MIN;
    }

    protected function _compare($prev, $actual) {
        if ($prev < $actual) {
            return $actual;
        } else {
            return $prev;
        }
    }

}