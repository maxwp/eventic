<?php
trait FSM_Trait {

    public function getState() {
        return $this->_state;
    }

    public function isState($state) {
        return $this->_state == $state;
    }

    protected $_state;

}