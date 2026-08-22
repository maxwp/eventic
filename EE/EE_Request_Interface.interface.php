<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

interface EE_Request_Interface {

    public function getArgumentArray();
    public function getArgument($key, $type = false);
    public function getArgumentSecure($key, $type = false);
    public function hasArgument($key);

}