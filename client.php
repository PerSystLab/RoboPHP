<?php
declare(strict_types=1);

$gloveDevice = '/tmp/glove_read';
$serverHost = '127.0.0.1';
$serverPort = 9000;

if (!file_exists($gloveDevice)) {
    fwrite(STDERR, "$gloveDevice not found.\n");
    exit(1);
}

$gloveFp = @fopen($gloveDevice, 'r');
if ($gloveFp === false) {
    fwrite(STDERR, "Cannot open $gloveDevice for reading.\n");
    exit(1);
}
stream_set_blocking($gloveFp, true);

// Connect to server
$socket = @stream_socket_client("tcp://$serverHost:$serverPort", $errno, $errstr, 5);
if ($socket === false) {
    fwrite(STDERR, "Connect error: $errstr ($errno)\n");
    exit(1);
}
stream_set_blocking($socket, true);
echo "Connected to server $serverHost:$serverPort, reading from $gloveDevice\n";

while (!feof($gloveFp)) {
    $line = fgets($gloveFp);
    if ($line === false) {
        usleep(50000);
        continue;
    }
    $line = trim($line);
    if ($line === '') {
        continue;
    }
    fwrite($socket, $line . "\n");
    fflush($socket);
}

fclose($socket);
fclose($gloveFp);
?>


