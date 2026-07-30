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

    // @todo типы source наверное относятся к особенностям Request?

    public function getArgumentArray();
    public function getArgument($key, $source = false);
    public const ARG_SOURCE_FILE = 'file';
    public const ARG_SOURCE_GET = 'get';
    public const ARG_SOURCE_POST = 'post';
    public const ARG_SOURCE_PUT = 'put'; // @todo
    public const ARG_SOURCE_DELETE = 'delete'; // @todo
    public const ARG_SOURCE_CLI = 'cli';
    public const ARG_SOURCE_INTERNAL = 'internal';

}