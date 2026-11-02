<?php
interface MA_Interface {

    /**
     * @param $ts
     * @param $value
     * @return mixed
     */
    public function update($ts, $value);
    public function get($ts);

}