<?php
final class EE_Call {

    public function __construct($target, EE_Request_Interface $request, EE_Response_Interface $response) {
        $this->_target = $target;
        $this->_request = $request;
        $this->_response = $response;
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

    /**
     * @return EE_Response_Interface
     */
    public function getResponse() {
        return $this->_response;
    }

    private $_target; // string classname
    private EE_Request_Interface $_request;
    private EE_Response_Interface $_response;

}