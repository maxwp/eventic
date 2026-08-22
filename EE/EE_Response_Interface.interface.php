<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Response interface
 */
interface EE_Response_Interface {

    public function getCode();

    public function setCode($code);

    public function getData();

    public function setData($data);

}