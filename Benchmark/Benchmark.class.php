<?php
class Benchmark extends EE_Content_Abstract_Cli {

    public function process() {
        $className = $this->getArgument('class', EE_Typing::TYPE_STRING);
        $simulateCount = $this->getArgumentSecure('count', EE_Typing::TYPE_INT);
        if (!$simulateCount) {
            $simulateCount = 10_000;
        }
        $simulateCold = $this->getArgumentSecure('cold', EE_Typing::TYPE_INT);

        $this->print_title("Benchmark", $className);

        $this->print_n("sim cnt = $simulateCount");
        $this->print_n();

        $stubObject = new Benchmark_Stub();
        /**
         * @var Benchmark_Interface
         */
        $testObject = new $className();

        // === HOT ===

        // сначала меряем пустой вызов и цикл
        $tAvg = [];
        for ($j = 1; $j <= $simulateCount; $j++) {
            $t = hrtime(true);
            $stubObject->process();
            $tAvg[] = hrtime(true) - $t;
        }
        $emptyCallHot = Array_Static::Avg($tAvg);

        $tHotAvg = [];
        for ($j = 1; $j <= $simulateCount; $j++) {
            $t = hrtime(true);
            $testObject->process();
            $tHotAvg[] = hrtime(true) - $t - $emptyCallHot;
        }

        $this->print_n("hot avg    = ".round(Array_Static::Avg($tHotAvg))." ns/call");
        $this->print_n("hot med    = ".round(Array_Static::Med($tHotAvg))." ns/call");
        $this->print_n("hot p95    = ".round(Array_Static::Quantile($tHotAvg, 95))." ns/call");
        $this->print_n("hot p99    = ".round(Array_Static::Quantile($tHotAvg, 99))." ns/call");
        $this->print_n("hot empty  = ".round($emptyCallHot)." ns/call");

        $this->print_n();

        // === COLD ===
        if ($simulateCold) {
            $tAvg = [];
            for ($j = 1; $j <= $simulateCount; $j++) {
                usleep($simulateCold); // пауза ДО измерения

                $t = hrtime(true);
                $stubObject->process();
                $tAvg[] = hrtime(true) - $t;
            }
            $emptyCallCold = Array_Static::Avg($tAvg);

            $tColdAvg = [];
            for ($j = 1; $j <= $simulateCount; $j++) {
                usleep($simulateCold); // пауза ДО измерения

                $t = hrtime(true);
                $testObject->process();
                $tColdAvg[] = hrtime(true) - $t - $emptyCallCold;
            }

            $this->print_n("cold avg   = " . round(Array_Static::Avg($tColdAvg)) . " ns/call");
            $this->print_n("cold med   = " . round(Array_Static::Med($tColdAvg)) . " ns/call");
            $this->print_n("cold p95   = " . round(Array_Static::Quantile($tColdAvg, 95)) . " ns/call");
            $this->print_n("cold p99   = " . round(Array_Static::Quantile($tColdAvg, 99)) . " ns/call");
            $this->print_n("cold empty = " . round($emptyCallCold) . " ns/call");
        }
    }

}