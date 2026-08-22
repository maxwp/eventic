<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

class EE_Routing_Cli implements EE_Routing_Interface {

    public function matchContent(EE_Request_Interface $request) {
        return $request->getArgument('ee', EE_Typing::TYPE_STRING);
    }

}