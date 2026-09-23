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

    public function add($className, $argumentArray = [], $uniquePID = false, $priority = Cron_Priority_Const::PRIORITY_DEFAULT, $logFile = false) {
        if (!is_subclass_of($className, EE_Content_Abstract::class)) {
            throw new Exception("Class $className is not subclass of EE_Content_Abstract");
        } elseif ($priority > +10) {
            throw new Exception("Priority must be between -10 and 10");
        } elseif ($priority < -10) {
            throw new Exception("Priority must be between -10 and 10");
        }

        $data = [
            'classname' => $className,
            'argumentArray' => $argumentArray,
            'pid' => $uniquePID,
            'priority' => $priority,
            'log' => $logFile,
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

            $command = $this->_makeCommand($data);

            // имя pid-файла
            $pid = $data['pid'];
            if (!$pid) {
                $pid = hash('fnv1a64', $command).'.pid';
            } elseif (!str_contains($pid, '.pid')) {
                $pid .= '.pid';
            }

            // только если приоритет для nice задан
            $priority = $data['priority'] ?? 0;
            if ($priority) {
                $priorityString = '/usr/bin/nice -n ' . $priority.' ';
            } else {
                $priorityString = '';
            }

            // custom log
            $log = $data['log'] ?? false;
            if ($log) {
                $logString = ">> $dirpath/log/$log 2>&1 &";
            } else {
                $logString = "> /dev/null 2>&1 &";
            }

            $cmd = "/usr/bin/flock -n $dirpath/pid/$pid $priorityString /usr/bin/php $dirpath/$command $logString";

            # debug:start
            Cli::Print_n(__CLASS__." run priority=$priority cmd=$cmd");
            # debug:end

            // запуск
            exec($cmd);

            // задержка между стартами
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