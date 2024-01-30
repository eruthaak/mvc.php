<?php
  if(route(1) === 'event') {
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    flush();
    for($i = 0; $i < 100; $i++){
      $data = json_encode(array('data' => $i));
      echo "event: onMessage\ndata: $data\n\n";
      flush();
      sleep(1);
    }
  } else {
    header('Content-Type: application/json');
    echo json_encode(['data' => 'Test']);
    exit;
  }
  