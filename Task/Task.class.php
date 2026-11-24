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
        return $this->_getRedis()->sAdd(
            'task',
            json_encode([$className, $argumentArray, $notificationKey])
        );
    }

    public function addTaskArray($taskArray) {
        if (!$taskArray) {
            return 0;
        }

        $a = [];
        foreach ($taskArray as [$className, $argumentArray, $notificationKey]) {
            $a[] = json_encode([$className, $argumentArray, $notificationKey]);
        }

        return $this->_getRedis()->sAdd(
            'task',
            ...$a
        );
    }

    /**
     * @return array
     * @throws Connection_Exception
     */
    public function getTaskArray() {
        $a = [];
        foreach ($this->_getRedis()->sMembers('task') as $data) {
            [$className, $argumentArray, $notificationKey] = json_decode($data, true);

            $a[] = [
                'className' => $className,
                'argumentArray' => $argumentArray,
                'notificationKey' => $notificationKey,
            ];
        }

        return $a;
    }

    public function clearTaskArray() {
        $this->_getRedis()->del('task');
    }

    public function process() {
        try {
            $redis = $this->_getRedis();

            while (true) {
                $data = $redis->sPop('task');

                if (!$data) {
                    // Очередь пустая -> проверим через 5 секунд
                    sleep(5);
                    continue;
                }

                [$className, $argumentArray, $notificationKey] = json_decode($data, true);

                # debug:start
                Cli::Print_t($className);
                Cli::Print_t(json_encode($argumentArray));
                Cli::Print_n();
                # debug:end

                try {
                    EE::Get()->execute(
                        new EE_Call(
                            $className,
                            new EE_Request_Array($argumentArray)
                        )
                    );

                    /*if ($notificationKey) {
                        _telegramSendLog("Task finished: $notificationKey");
                    }*/
                } catch (Throwable $t) {
                    // @todo костыляка с зависимостью
                    if ($notificationKey) {
                        _telegramSendLog(
                            "Task error: $notificationKey ".$t->getMessage()
                        );
                    }

                    throw $t;
                } finally {
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