<?php
class SuperVisor extends Pattern_ASingleton {

    public function register($superID, $className, $argumentArray, $ttl = 300) {
        $redis = Connection::GetRedis()->getLink();

        $data = [
            'className' => $className,
            'argumentArray' => $argumentArray,
        ];
        $data = serialize($data);

        $redis->sAdd('supervisor', $superID);
        $redis->set('supervisor:'.$superID, $data, $ttl);

        # debug:start
        Cli::Print_n(__CLASS__.": register $superID");
        # debug:end
    }

    public function unregister($superID) {
        $redis = Connection::GetRedis()->getLink();
        $redis->srem('supervisor', $superID);
        $redis->del('supervisor:'.$superID);

        # debug:start
        Cli::Print_n(__CLASS__.": unregister $superID");
        # debug:end
    }

    public function clear() {
        $redis = Connection::GetRedis()->getLink();
        $redis->del('supervisor');
        $redis->del('supervisor:*');
    }

    public function process() {
        $redis = Connection::GetRedis()->getLink();

        $a = $redis->sMembers('supervisor');
        $needArray = [];
        foreach ($a as $superID) {
            $data = $redis->get('supervisor:'.$superID);

            // если есть данные - пробуем сделать unserialize
            if ($data) {
                // сначала строим hash от всех данных процесса: если что-то поменяется - то процесс надо будет килять
                $superHash = md5($data);
                $data = unserialize($data);
            }

            // если вдруг ничего нет - то удаляем из members set
            if (!$data) {
                $this->unregister($superID);
                continue;
            }

            // @todo формирование команд надо сделать универсально
            // @todo Cron это скорее ProcessManager с разными списками?

            // список того что должно быть запущено: id + hash
            $needArray[$superID][$superHash] = true;

            Cron::Get()->add(
                SuperRun::class,
                [
                    'superid' => $superID,
                    'superhash' => $superHash, // hash of data
                    //'superport' => crc32($superID) % 5000 + 5003, // определяем superport который будет передан как аргумент @todo
                ],
                md5($superID) // pid
            );
        }

        // получаем список процессов и киляем все чего нет в supervisor
        $a = [];
        exec("ps -eo pid=,cmd= | grep SuperRun | grep -v flock", $a);
        foreach ($a as $line) {
            // @todo поменять на более правильный разбор через ProcessManager,
            //       а то я тут сильно доверяю порядку аргументов
            if (preg_match("/^(\d+).+?SuperRun.+?superhash=(\S+).+?superid=(\S+)/", trim($line), $r)) {
                //$pid = $r[1];
                //$superID = $r[3];
                //$superHash = $r[2];

                // нет такого superID + superHash - надо убивать процесс
                if (empty($needArray[$r[3]][$r[2]])) {
                    # debug:start
                    Cli::Print_n(__CLASS__.' kill '.$r[3].' '.$r[2].' pid='.$r[1]);
                    # debug:end
                    exec('kill '.$r[1]);
                }
            }
        }
    }

    /**
     * @return array
     * @throws Connection_Exception
     */
    public function getConfigArray() {
        $redis = Connection::GetRedis()->getLink();
        $a = $redis->sMembers('supervisor');
        $b = [];
        foreach ($a as $superID) {
            try {
                $b[$superID] = $this->getConfig($superID);
            } catch (Exception) {

            }
        }
        return $b;
    }

    public function getConfig($superID) {
        $redis = Connection::GetRedis()->getLink();
        $data = $redis->get('supervisor:'.$superID);

        // если есть данные - пробуем сделать unserialize
        if ($data) {
            $data = unserialize($data);
        }

        if (!$data) {
            throw new Exception(__CLASS__.": no superconfig found for $superID");
        } elseif (empty($data['className'])) {
            throw new Exception(__CLASS__.": no className found for $superID");
        } elseif (!isset($data['argumentArray'])) {
            throw new Exception(__CLASS__.": no argumentArray found for $superID");
        } else {
            return $data;
        }
    }

    public function __construct() {
        // stub for singleton
    }

}