<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Content interface
 */
interface EE_Content_Interface {

    public function process();

    public function render();

    public function getValue($key);

    public function setValue($key, $value);

    public function getValueArray();

    public function reset();

}