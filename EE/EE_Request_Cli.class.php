<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

class EE_Request_Cli implements EE_Request_Interface {

    public function getArgumentArray() {
        return $this->_argumentArray;
    }

    public function getArgument($key, $type = false) {
        // @todo общий код с Cli
        if (isset($this->_argumentArray[$key])) {
            $x = $this->_argumentArray[$key];

            // опциональная типизация
            if ($type) {
                return EE_Typing::TypeValue($x, $type);
            } else {
                return $x;
            }
        } else {
            throw new EE_Exception('No argument ' . $key);
        }
    }

    public function getArgumentSecure($key, $type = false) {
        // @todo можно вынести в Request_Abstract
        try {
            return $this->getArgument($key, $type);
        } catch (Exception) {
            if ($type) {
                return EE_Typing::TypeValue(false, $type);
            } else {
                return false;
            }
        }
    }

    public function hasArgument($key) {
        return isset($this->_argumentArray[$key]);
    }

    public function __construct() {
        global $argv;

        // один раз парсим все аргументы и сохраняем их в локальный массив

        $argumentArray = [];

        if (!empty($argv[1])) {
            $argumentArray['ee'] = $argv[1];
        }

        // косим нулевой и первый элементы - там тупо ee-run.php и имя класса,
        // причем они закосятся глобально и в следующем запуске не будет даже ee
        unset($argv[0], $argv[1]);

        foreach ($argv as $arg) {
            if ($arg) {
                if (preg_match("/^(.+?)=\[(.+?)\]$/ius", $arg, $r)) {
                    // key=[a,b,c]
                    $argumentArray[$r[1]] = explode(',', $r[2]);
                } elseif (preg_match("/^(.+?)=(.+?)$/ius", $arg, $r)) {
                    // key=value
                    $argumentArray[$r[1]] = $r[2];
                } else {
                    // key
                    // @todo тут опасно ставить true, потому что для string без значения я поставлю 1
                    $argumentArray[$arg] = true;
                }
            }
        }

        $this->_argumentArray = $argumentArray;
    }

    /**
     * @var array
     */
    private $_argumentArray;

}