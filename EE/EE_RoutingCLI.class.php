<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Routing for CLI
 */
class EE_RoutingCLI implements EE_IRouting {

    public function matchContent(EE_IRequest $request) {
        $a = $request->getArgumentArray();

        if (isset($a['ee'])) {
            return $a['ee'];
        } else {
            throw new EE_Exception("No class argv[1]");
        }
    }

}