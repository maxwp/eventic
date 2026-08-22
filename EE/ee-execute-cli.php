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

print "\n\n";
print "done:\n";
print "pid      = ".getmypid()."\n";
print "start    = ".date('Y-m-d H:i:s', $tsStart)." ($tsStart)\n";
print "finish   = ".date('Y-m-d H:i:s', $tsFinish)." ($tsFinish)\n";
print "duration = ".round($tsFinish - $tsStart)." sec.\n";
print "mem peak = ".round(memory_get_peak_usage(true) / 1024 / 1024, 2)." mb.\n";
print "\n";

exit(0);