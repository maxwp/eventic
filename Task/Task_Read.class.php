<?php
class Task_Read extends EE_Content_Abstract_Cli {

    public function process() {
        $this->print_title('Create tasks');

        while (($line = fgets(STDIN)) !== false) {
            $line = rtrim($line);

            if ($line) {
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

            // выход
            if ($line == 'q') {
                break;
            } elseif ($line == 'quit') {
                break;
            } elseif ($line == 'exit') {
                break;
            }
        }
    }

}