<?php
final class EE_Call {

    public function __construct($target, EE_IRequest $request, EE_IResponse $response) {
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
     * @return EE_IRequest
     */
    public function getRequest() {
        return $this->_request;
    }

    /**
     * @return EE_IResponse
     */
    public function getResponse() {
        return $this->_response;
    }

    private $_target; // string classname
    private EE_IRequest $_request;
    private EE_IResponse $_response;

}