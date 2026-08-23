<?php
class Task_Worker extends EE_Content_Abstract_Cli {

    public function process() {
        Task::Get()->process();
    }

}