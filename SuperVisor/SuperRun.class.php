<?php
class SuperRun extends EE_Content_Abstract_Cli {

    public function process() {
        $superID = $this->getArgument('superid', EE_Typing::TYPE_STRING);
        $superConfig = SuperVisor::Get()->getConfig(
            $superID
        );

        // принудительный захват
        if ($this->getArgumentSecure('claim', EE_Typing::TYPE_BOOL)) {
            $this->print_t("claiming $superID...");

            $pidMy = getmypid();
            $pidArray = [];
            exec('pgrep -f ' . escapeshellarg($superID), $pidArray);

            foreach ($pidArray as $pid) {
                $pid = (int)$pid;
                if ($pid != $pidMy) {
                    posix_kill($pid, SIGTERM);
                }
            }

            $this->print_n('done');
        }

        # debug:start
        $this->print_r($superConfig);
        # debug:end

        $className = $superConfig['className'];
        $argumentArray = $superConfig['argumentArray'];

        // создаем объект
        // и ебашим в него аргументы
        EE::Get()->execute(
            new EE_Call(
                $className,
                new EE_Request_Array($argumentArray)
            )
        );
    }

}