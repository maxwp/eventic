<?php
final class EE_Call {

    public function __construct($target, EE_Request_Interface $request) {
        $this->_target = $target;
        $this->_request = $request;
    }

    /**
     * @return string
     */
    public function getTarget() {
        return $this->_target;
    }

    /**
     * @return EE_Request_Interface
     */
    public function getRequest() {
        return $this->_request;
    }

    private $_target; // string classname
    private EE_Request_Interface $_request;

}