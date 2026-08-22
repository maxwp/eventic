<?php
$tsStart = microtime(true);

$routing = new EE_Routing_Cli();
$request = new EE_Request_Cli();
$response = new EE_Response_Cli();

EE::Get()->setRouting($routing);
EE::Get()->execute($request, $response);

$tsFinish = microtime(true);

$data = $response->getData();
if ($data) {
    print_r($data);
}

$exitCode = $response->getCode();

print "\n\n";
print "done:\n";
print "pid      = ".getmypid()."\n";
print "code     = ".$exitCode."\n";
print "start    = ".date('Y-m-d H:i:s', $tsStart)." ($tsStart)\n";
print "finish   = ".date('Y-m-d H:i:s', $tsFinish)." ($tsFinish)\n";
print "duration = ".round($tsFinish - $tsStart)." sec.\n";
print "mem peak = ".round(memory_get_peak_usage(true) / 1024 / 1024, 2)." mb.\n";
print "\n";

exit($exitCode);