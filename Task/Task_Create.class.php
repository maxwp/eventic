<?php
class Task_Create extends EE_Content_Abstract_Cli {

    public function process() {
        $className = $this->getArgument('class', EE_Typing::TYPE_STRING);

        $argumentArray = $this->getArgumentArray();
        unset($argumentArray['class']);
        ksort($argumentArray);

        $a = [];
        foreach ($argumentArray as $key => $value) {
            if (is_array($value)) {
                $a[] = $key . '=[' . implode(',', $value) . ']';
            } elseif ($value === true) {
                $a[] = $key;
            } else {
                $a[] = "$key=$value";
            }
        }
        $notificationKey = $className.' '.implode(' ', $a);

        $result = Task::Get()->addTask(
            $className,
            $argumentArray,
            $notificationKey
        );

        $this->print_n("Task created. nk=$notificationKey result=$result");
    }

}