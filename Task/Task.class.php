<?php
class Task extends Pattern_ASingleton {

    public static function InitWorkers($cnt) {
        for ($j = 1; $j <= $cnt; $j++) {
            Cron::Get()->add(
                Task_Worker::class,
                ['thread' => $j],
            );
        }
    }

    public function addTask($className, $argumentArray, $notificationKey = false) {
        // @todo set?
        return $this->_getRedis()->lPush(
            'task',
            json_encode([$className, $argumentArray, $notificationKey])
        );
    }

    public function getTaskArray() {
        $a = $this->_getRedis()->lRange('task', 0, -1);
        $b = [];
        foreach ($a as $x) {
            [$className, $argumentArray, $notificationKey] = json_decode($x, true);
            $b[] = [
                'className' => $className,
                'argumentArray' => $argumentArray,
                'notificationKey' => $notificationKey,
            ];
        }
        return $b;
    }

    public function clearTaskArray() {
        $this->_getRedis()->del('task');
    }

    public function process() {
        try {
            $redis = $this->_getRedis();

            while ($data = $redis->brPop('task', 3600)) {
                [$className, $argumentArray, $notificationKey] = json_decode($data[1], true);

                try {
                    EE::Get()->execute(new EE_Call($className, new EE_Request_Array($argumentArray)));

                    if ($notificationKey) {
                        //_telegramSendLog("Task finished: $notificationKey");
                    }
                } catch (Throwable $t) {
                    if ($notificationKey) {
                        _telegramSendLog("Task error: $notificationKey ".$t->getMessage());
                    }

                    throw $t;
                } finally {
                    // cleanup
                    unset($block);
                    gc_collect_cycles();
                }
            }
        } catch (RedisException) {
            // выход по тихому на redis timeout
            return;
        } catch (Exception $e) {
            throw $e;
        } catch (Throwable $t) {
            throw $t;
        }
    }

    /**
     * @return Redis
     * @throws Connection_Exception
     */
    private function _getRedis() {
        return Connection::GetRedis()->getLink();
    }

    public function __construct() {
        // stub
    }

}