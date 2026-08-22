<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Request for CLI
 */
class EE_Request_Cli implements EE_Request_Interface {

    // @todo отказаться от говна с одинаковыми аргументами, лучше явно массивы
    // @todo отказаться о -- prefix
    // @todo общий код с getArgument? или вписать все через init getArgumments?

    public function getArgumentArray() {
        global $argv;

        $argumentArray = [];

        if (!empty($argv[1])) {
            $argumentArray['ee'] = $argv[1];
        }

        // косим нулевой и первый элементы - там тупо ee-run.php и имя класса,
        // причем они закосятся глобально и в следующем запуске не будет даже ee
        // @todo разобраться нужно ли так
        unset($argv[0], $argv[1]);

        foreach ($argv as $arg) {
            if ($arg) {
                $arg = preg_replace('/^--/', '', $arg);

                if (preg_match("/^(.+?)=\[(.+?)\]$/ius", $arg, $r)) {
                    $argumentArray[$r[1]] = explode(',', $r[2]);
                } elseif (preg_match("/^(.+?)=(.+?)$/ius", $arg, $r)) {
                    $argumentArray[$r[1]] = $r[2];
                } else {
                    // похоже на bool
                    $argumentArray[$arg] = true;
                }
            }
        }

        return $argumentArray;
    }

    public function getArgument($key, $source = false) {
        global $argv;

        // проверка на дурачка
        if ($source && $source != self::ARG_SOURCE_CLI) {
            throw new EE_Exception("Cli has only source CLI arguments");
        }

        $key = str_replace('--', '', $key);
        if (!$key) {
            throw new EE_Exception('no arg name', 1);
        }

        $returnArray = [];
        for ($j = 1; $j <= 100; $j++) {
            $arg = @$argv[$j];
            if (!$arg) {
                continue;
            }

            $arg = preg_replace('/^--/', '', $arg);

            if (preg_match("/^(.+?)=\[(.+?)\]$/ius", $arg, $r)) {
                if ($r[1] == $key) {
                    $returnArray = array_merge($returnArray, explode(',', $r[2]));
                }
            } elseif (preg_match("/^(.+?)=(.+?)$/ius", $arg, $r)) {
                if ($r[1] == $key) {
                    $returnArray[] = $r[2];
                }
            } elseif ($arg == $key) {
                // похоже на bool
                $returnArray[] = true;
            }
        }

        if ($returnArray) {
            // если один элемент - выдаем его
            if (count($returnArray) == 1) {
                return $returnArray[0];
            } else {
                // инача массив
                return $returnArray;
            }
        }

        throw new EE_Exception('No argument '.$key);
    }

}