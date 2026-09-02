<?php
class SuperVisor extends Pattern_ASingleton {

    public function register($className, $argumentArray, $ttl = 300) {
        $redis = Connection::GetRedis()->getLink();

        $data = [
            'className' => $className,
            'argumentArray' => $argumentArray,
        ];
        $data = serialize($data);

        // автоматизированно строим superID: в нем теперь класс + аргументы.
        // так надо делать, чтобы если поменяются аргументы - то я кильнул процесс тоже.
        $superID = $className.':'.md5($data);

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

    public function process() {
        $redis = Connection::GetRedis()->getLink();

        $a = $redis->sMembers('supervisor');
        $idArray = [];
        foreach ($a as $superID) {
            $data = $redis->get('supervisor:'.$superID);

            // если есть данные - пробуем сделать unserialize
            if ($data) {
                $data = unserialize($data);
            }

            // если вдруг ничего нет - то удаляем из members set
            if (!$data) {
                $this->unregister($superID);
                continue;
            }

            // @todo формирование команд надо сделать универсально
            // @todo Cron это скорее ProcessManager с разными списками?

            // список того что должно быть запущено
            $idArray[$superID] = $superID;

            Cron::Get()->add(
                SuperRun::class,
                [
                    'superid' => $superID,
                    //'superport' => crc32($superID) % 5000 + 5003, // определяем superport который будет передан как аргумент @todo
                ],
                md5($superID) // pid
            );
        }

        // получаем список процессов и киляем все чего нет в supervisor
        $a = [];
        exec("ps -eo pid=,cmd= | grep SuperRun | grep -v flock", $a);
        foreach ($a as $line) {
            $line = trim($line);
            if (preg_match("/^(\d+).+?SuperRun.+?superid=(\S+)/ius", $line, $r)) {
                $pid = $r[1];
                $superID = $r[2];

                if (empty($idArray[$superID])) {
                    exec("kill $pid");
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
        }

        return $data;
    }

    public function __construct() {
        // stub for singleton
    }

}