<?php
declare(strict_types=1);

$host = '127.0.0.1';
$port = 9000;
$robotDevice = '/tmp/robot_write';

$robotFp = @fopen($robotDevice, 'w');
if ($robotFp === false) {
    fwrite(STDERR, "Cannot open $robotDevice for writing.\n");
    exit(1);
}
stream_set_blocking($robotFp, true);

// Create TCP server
$server = @stream_socket_server("tcp://$host:$port", $errno, $errstr);
if ($server === false) {
    fwrite(STDERR, "Server error: $errstr ($errno)\n");
    exit(1);
}
echo "PHP server listening on $host:$port, forwarding to $robotDevice\n";

// Accept loop
while (true) {
    $client = @stream_socket_accept($server, 300);
    if ($client === false) {

        continue;
    }
    $peer = stream_socket_get_name($client, true);
    echo "Client connected: $peer\n";
    stream_set_blocking($client, true);

    $startNs = hrtime(true);
    $prevNs = $startNs;
    $msgCount = 0;

    while (!feof($client)) {
        $line = fgets($client);
        if ($line === false) {
            break;
        }
        $line = trim($line);
        if ($line === '') {
            continue;
        }

        if (preg_match('/^\s*([+-]?\d+)\s*,\s*([+-]?\d+)\s*,\s*([+-]?\d+)\s*,\s*([+-]?\d+)\s*,\s*([+-]?\d+)\s*$/', $line, $m)) {
            fwrite($robotFp, $line . "\n");
            fflush($robotFp);

            $nowNs = hrtime(true);
            $dtMs = (int) round(($nowNs - $prevNs) / 1_000_000);
            $totalMs = (int) round(($nowNs - $startNs) / 1_000_000);
            $msgCount++;
            $rate = $totalMs > 0 ? ($msgCount * 1000.0 / $totalMs) : 0.0; 
            echo "[$peer] t={$totalMs}ms dt={$dtMs}ms n={$msgCount} rate=" . number_format($rate, 2) . " msg/s\n";
            $prevNs = $nowNs;
        } else {
            
        }
    }

    fclose($client);
    echo "Client disconnected: $peer\n";
}

fclose($robotFp);
?>


