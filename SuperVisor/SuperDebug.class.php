<?php
class SuperDebug extends EE_Content_Abstract_Cli {

    public function process() {
        // очистка всего супервизора
        if ($this->getArgumentSecure('clear', EE_Typing::TYPE_BOOL)) {
            SuperVisor::Get()->clear();

            $this->print_n_success('SuperVisor fully cleared');
            $this->print_n();
        }

        $superArray = SuperVisor::Get()->getConfigArray();

        # debug:start
        $this->print_r($superArray);
        # debug:end

        foreach ($superArray as $key => $a) {
            $this->print_t($key);
            $this->print_t($a['className']);
            $this->print_n();

            $this->print_t(json_encode($a['argumentArray']));
            $this->print_n();
            $this->print_n();
        }
    }

}