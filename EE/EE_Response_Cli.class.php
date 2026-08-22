<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Response for CLI
 */
class EE_Response_Cli implements EE_Response_Interface {

    public function getCode() {
        return $this->_code;
    }

    public function getData() {
        return $this->_data;
    }

    public function setCode($code) {
        $this->_code = $code;
    }

    public function setData($data) {
        $this->_data = $data;
    }

    private int $_code = 0; // для CLI код выхода 0

    private $_data;

}