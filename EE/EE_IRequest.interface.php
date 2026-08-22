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
interface EE_IRequest {

    public function getArgumentArray();
    public function getArgument($key, $source = false);
    //public function hasArgument(string $key): bool;
    public const ARG_SOURCE_CLI = 'cli';
    public const ARG_SOURCE_INTERNAL = 'internal';

}