<?php
class SuperRun extends EE_AContentCli {

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
        $object = new $className();
        /**
         * @var EE_AContent $object
         */
        foreach ($argumentArray as $key => $value) {
            $object->setArgument($key, $value);
        }

        // запускаем
        $object->process();
    }

}