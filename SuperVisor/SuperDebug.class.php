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

        foreach ($superArray as $superID => $a) {
            $this->print_t($superID);
            $this->print_t($a['className']);

            $priority = $a['priority'];
            if ($priority == Cron_Priority_Const::PRIORITY_HIGH) {
                $this->printSGRStart(Cli::FG_RED_BRIGHT);
                $this->print_t('priority=high');
            } elseif ($priority == Cron_Priority_Const::PRIORITY_LOW) {
                $this->printSGRStart(Cli::FG_GREEN_BRIGHT);
                $this->print_t('priority=low');
            } elseif ($priority == Cron_Priority_Const::PRIORITY_DEFAULT) {
                $this->printSGRStart(Cli::FG_YELLOW_BRIGHT);
                $this->print_t('priority=default');
            } else {
                $this->printSGRStart(Cli::BG_ORANGE);
                $this->print_t('priority='.$priority);
            }
            $this->printSGREnd();
            $this->print_n();

            $this->print_t(json_encode($a['argumentArray']));
            $this->print_n();
            $this->print_n();
        }
    }

}