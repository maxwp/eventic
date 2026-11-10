<?php
class Task_Cli extends EE_Content_Abstract_Cli {

    public function process() {
        $this->print_title('Create tasks');

        while (true) {
            $this->print('cmd: ');
            $line = fgets(STDIN);
            $line = rtrim($line);

            // выход
            if ($line == 'q') {
                break;
            } elseif ($line == 'quit') {
                break;
            } elseif ($line == 'exit') {
                break;
            } elseif ($line == 'show') {
                // показат все команды
                $this->print_header1('Task que');
                $taskArray = Task::Get()->getTaskArray();
                foreach ($taskArray as $x) {
                    $this->print_t($x['className']);
                    $this->print_t(json_encode($x['argumentArray']));
                    $this->print_n();
                }
            } elseif ($line == 'clear') {
                Task::Get()->clearTaskArray();
                $this->print_n_success('cleared');
            } elseif ($line) {
                // @todo парсим ee-команду, пока-что через shell-очко
                if (preg_match("/^php ee-run.php (.+?)$/ius", $line, $r)) {
                    $line = "php ee-run.php ".Task_Create::class.' class='.$r[1];
                } elseif (preg_match("/^ee (.+?) (.+?)$/ius", $line, $r)) {
                    $line = "php ee-run.php ".Task_Create::class.' class='.$r[1].' '.$r[2];
                } else {
                    $this->print_n_failure("Unknown ee-command");
                    continue;
                }

                exec($line);
                $this->print_n_success('created: '.$line);
            }
        }
    }

}