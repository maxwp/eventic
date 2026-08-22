<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Request interface
 */
interface EE_Request_Interface {

    public function getArgument($key, $source = false);
    public function getArgumentArray();
    //public function getArgument($key, $type = false);
    //public function getArgumentSecure($key, $type = false);
    //public function hasArgument($key): bool;
    /**
     * @deprecated
     */
    public const ARG_SOURCE_CLI = 'cli';
    /**
     * @deprecated
     */
    public const ARG_SOURCE_INTERNAL = 'internal';

}