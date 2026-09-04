<?php

if(method() == 'POST') {
  $websocket_client = new WebSocket_Client('192.168.1.191', 9000);
  $websocket_client->send(json_encode(array('data' => 'Hi, I\'m Yusuf!')));
}
require view('index');