<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2026 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Cron
 */
class Cron extends Pattern_ASingleton {

    public function add($className, $argumentArray = [], $uniquePID = false, $logFile = false) {
        if (!is_subclass_of($className, EE_AContent_Interface::class)) {
            throw new Exception("Class $className does not extend EE_AContent");
        }

        $data = [
            'classname' => $className,
            'argumentArray' => $argumentArray,
            'pid' => $uniquePID,
            'logFile' => $logFile,
        ];

        $result = $this->_redis->sAdd('cron', json_encode($data));

        # debug:start
        $command = $this->_makeCommand($data);
        Cli::Print_n("Cron: add $command ($result)");
        # debug:end
    }

    public function run($dirpath, $delayUS = false) {
        while ($file = $this->_redis->sPop('cron')) {
            $data = json_decode($file, true);

            $pid = $data['pid'];
            $logFile = $data['logFile'];

            $command = $this->_makeCommand($data);

            // строим имя pid'a если его нет
            if (!$pid) {
                $pid = hash('fnv1a64', $command);
            }
            if (!str_contains($pid, '.pid')) {
                $pid .= '.pid';
            }

            if ($logFile) {
                $logString = ">> $dirpath/log/$logFile 2>&1 &";
            } else {
                $logString = "> /dev/null 2>&1 &";
            }

            $path = "/usr/bin/flock -n $dirpath/pid/$pid /usr/bin/php $dirpath/$command $logString";

            # debug:start
            Cli::Print_n("Cron: run $path");
            # debug:end

            exec($path);

            if ($delayUS) {
                usleep($delayUS);
            }
        }
    }

    // @todo универсальный метод построения команды в Cli::
    private function _makeCommand($data) {
        $className = $data['classname'];
        $argumentArray = $data['argumentArray'];

        if ($argumentArray) {
            ksort($argumentArray);

            $a = [];
            foreach ($argumentArray as $key => $value) {
                if (is_array($value)) {
                    $a[] = $key . '=[' . implode(',', $value) . ']';
                } elseif ($value === true) {
                    $a[] = $key;
                } else {
                    $a[] = "$key=$value";
                }
            }
            $argumentString = implode(' ', $a);
            unset($a);
        } else {
            $argumentString = '';
        }

        return "ee-run.php $className $argumentString";
    }

    public function clear() {
        $this->_redis->del('cron');
    }

    public function __construct() {
        // именно локальный redis
        $this->_redis = new Redis();
        $this->_redis->connect('127.0.0.1');
        return $this->_redis;
    }

    private $_redis;

}