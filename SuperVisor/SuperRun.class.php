<?php
class SuperRun extends EE_Content_Abstract_Cli {

    public function process() {
        $superConfig = SuperVisor::Get()->getConfig(
            $this->getArgument('superid', EE_Typing::TYPE_STRING)
        );

        # debug:start
        $this->print_r($superConfig);
        # debug:end

        $className = $superConfig['className'];
        $argumentArray = $superConfig['argumentArray'];

        // создаем объект
        // и ебашим в него аргументы
        $result = EE::Get()->execute(
            new EE_Call(
                $className,
                new EE_Request_Array($argumentArray)
            )
        );
    }

}