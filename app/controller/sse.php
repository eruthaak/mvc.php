<?php

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');


$data = json_encode(array("Test"));
echo "data: $data".PHP_EOL.PHP_EOL;
ob_flush();
flush();