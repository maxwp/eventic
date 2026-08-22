<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Dispatcher
 */
class EE extends Pattern_ASingleton {

    /**
     * @param EE_Call $call
     * @return mixed
     */
    public function execute(EE_Call $call) {
        $className = $call->getTarget();

        $content = new $className();
        /**
         * @var EE_Content_Interface $content
         */

        return $content->main(
            $call->getRequest()
        );
    }

    protected function __construct() {
        // stub
    }

}