<?php
class Benchmark extends EE_Content_Abstract_Cli {

    public function process() {
        $className = $this->getArgument('class', EE_Typing::TYPE_STRING);
        $simulateCount = $this->getArgumentSecure('count', EE_Typing::TYPE_INT);
        if (!$simulateCount) {
            $simulateCount = 10_000;
        }
        $simulateCold = $this->getArgumentSecure('cold', EE_Typing::TYPE_INT);

        $this->print_title("Benchmark $className");

        $stub = new Benchmark_Stub();

        // сначала меряем пустой вызов и цикл
        $tAvg = [];
        for ($simulation = 1; $simulation <= 10; $simulation++) {
            $t = hrtime(true);

            for ($j = 1; $j <= $simulateCount; $j++) {
                $stub->process();
            }

            $t = hrtime(true) - $t;
            $tAvg[] = $t / $simulateCount;
        }
        $emptyCallHot = Array_Static::Avg($tAvg);

        if ($simulateCold) {
            $tAvg = [];
            for ($simulation = 1; $simulation <= 10; $simulation++) {
                $t = hrtime(true);

                for ($j = 1; $j <= $simulateCount; $j++) {
                    $stub->process();
                    usleep($simulateCold);
                }

                $t = hrtime(true) - $t;
                $tAvg[] = $t / $simulateCount;
            }
            $emptyCallCold = Array_Static::Avg($tAvg);
        }

        // затем меряем реальный вызов

        /**
         * @var Benchmark_Interface
         */
        $testObject = new $className();

        $tHotAvg = [];
        for ($simulation = 1; $simulation <= 10; $simulation++) {
            $t = hrtime(true);

            for ($j = 1; $j <= $simulateCount; $j++) {
                $testObject->process();
            }

            $t = hrtime(true) - $t;
            $tx = $t / $simulateCount - $emptyCallHot;
            $this->print_n(round($tx).' hot ns/call');
            $tHotAvg[] = $tx;
        }

        $this->print_n();

        if ($simulateCold) {
            $tColdAvg = [];
            for ($simulation = 1; $simulation <= 10; $simulation++) {
                $t = hrtime(true);

                for ($j = 1; $j <= $simulateCount; $j++) {
                    $testObject->process();
                    usleep($simulateCold);
                }

                $t = hrtime(true) - $t;
                $tx = $t / $simulateCount - $emptyCallCold;
                $this->print_n(round($tx) . ' cold ns/call');
                $tColdAvg[] = $tx;
            }
        }

        $this->print_break();
        //$this->print_n("empty       = ".round($emptyCallHot)." ns/call");
        $this->print_n("sim cnt = $simulateCount");

        $this->print_n("hot  avg = ".round(Array_Static::Avg($tHotAvg))." ns/call");
        $this->print_n("hot  med = ".round(Array_Static::Med($tHotAvg))." ns/call");
        $this->print_n("hot  p95 = ".round(Array_Static::Quantile($tHotAvg, 95))." ns/call");
        $this->print_n("hot  p99 = ".round(Array_Static::Quantile($tHotAvg, 99))." ns/call");

        $this->print_n();

        if ($simulateCold) {
            $this->print_n("cold avg = " . round(Array_Static::Avg($tColdAvg)) . " ns/call");
            $this->print_n("cold med = " . round(Array_Static::Med($tColdAvg)) . " ns/call");
            $this->print_n("cold p95 = " . round(Array_Static::Quantile($tColdAvg, 95)) . " ns/call");
            $this->print_n("cold p99 = " . round(Array_Static::Quantile($tColdAvg, 99)) . " ns/call");
        }
    }

}