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

    /**
     * Метод вернет результат (main returns)
     *
     * @param EE_Request_Interface $request
     * @return mixed
     */
    public function main(EE_Request_Interface $request);

}