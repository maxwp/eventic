<?php
$tsStart = microtime(true);

$routing = new EE_Routing_Cli();
$request = new EE_Request_Cli();

$call = new EE_Call(
    $routing->matchContent($request),
    $request,
);

EE::Get()->execute($call);

$tsFinish = microtime(true);

Cli::Print_Header2(
    'ee:done',
     "pid      = ".getmypid(),
    "start    = ".date('Y-m-d H:i:s', $tsStart)." ($tsStart)",
    "finish   = ".date('Y-m-d H:i:s', $tsFinish)." ($tsFinish)",
    "duration = ".round($tsFinish - $tsStart)." sec",
    "mem peak = ".round(memory_get_peak_usage(true) / 1024 / 1024)." mb",
);

exit(0);