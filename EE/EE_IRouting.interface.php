<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Routing interface
 */
interface EE_IRouting {

    // по IRequest мы определяем имя IProcessable-класса (контента), с которого будет запуск движка
    public function matchContent(EE_Request_Interface $request);

}