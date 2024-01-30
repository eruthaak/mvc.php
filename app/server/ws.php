<?php
	const WS_HOST = 'localhost'; // Host
	const WS_PORT = '9000'; // Port
  $null = null;

	$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP); // Create TCP/IP sream socket
	socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1); // Reuseable port
	socket_bind($socket, WS_HOST, WS_PORT); // Bind socket to specified host
	socket_listen($socket); // Listen to port
	$clients = array($socket); // Create & add listening socket to the list

	while (true) { // Start endless loop, so that our script doesn't stop
		$changed = $clients; // Manage multiple connections

		socket_select($changed, $null, $null, 0, 10); // Returns the socket resources in $changed array
		if (in_array($socket, $changed)) /* Check for new socket */ {
			$newSocket = socket_accept($socket); // Accept new socket
			$clients[] = $newSocket; // Add socket to client array

			$header = socket_read($newSocket, 1024); // Read data sent by the socket
			handshake($header, $newSocket, WS_HOST, WS_PORT); // Perform websocket handshake

			// socket_getpeername($newSocket, $ip); // Get IP address of connected socket.
			// $response = mask(json_encode(array('type'=>'system', 'message'=>$ip.' connected'))); // Prepare json data
			// send($response); // Notify all users about new connection

			$foundSocket = array_search($socket, $changed); // Make room for new socket
			unset($changed[$foundSocket]);
		}

		foreach ($changed as $changedSocket) {	// Loop through all connected sockets
      while(socket_recv($changedSocket, $buf, 1024, 0) >= 1) { // Check for any incomming data
				$received_text = unmask($buf); // Unmask data
				$data = (array) json_decode($received_text, true); // Json decode

				$response_text = mask(json_encode($data)); // Prepare data to be sent to client
				send($response_text); // Send data
				break 2; // Exist this loop
			}

      $buf = @socket_read($changedSocket, 1024, PHP_NORMAL_READ);
			if ($buf === false) { // Check disconnected client
				$found_socket = array_search($changedSocket, $clients); // Remove client for $clients array
				socket_getpeername($changedSocket, $ip);
				unset($clients[$found_socket]);

				// Notify all users about disconnected connection

				// $response = mask(json_encode(array('type'=>'system', 'message'=>$ip.' disconnected')));
				// send($response);
			}
		}
	}

	socket_close($socket); // Close the listening socket

	function send($msg) {
		global $clients;
		foreach($clients as $changedSocket) {
			@socket_write($changedSocket, $msg, strlen($msg));
		}
		return true;
	}

	// Unmask incoming framed message
	function unmask($text) {
		$length = ord($text[1]) & 127;
		if($length == 126) {
			$masks = substr($text, 4, 4);
			$data = substr($text, 8);
		}
		elseif($length == 127) {
			$masks = substr($text, 10, 4);
			$data = substr($text, 14);
		}
		else {
			$masks = substr($text, 2, 4);
			$data = substr($text, 6);
		}
		$text = "";
		for ($i = 0; $i < strlen($data); ++$i) {
			$text .= $data[$i] ^ $masks[$i%4];
		}
		return $text;
	}

	// Encode message for transfer to client.
	function mask($text) {
		$b1 = 0x80 | (0x1 & 0x0f);
		$length = strlen($text);
		
		if($length <= 125)
			$header = pack('CC', $b1, $length);
		elseif($length > 125 && $length < 65536)
			$header = pack('CCn', $b1, 126, $length);
		elseif($length >= 65536)
			$header = pack('CCNN', $b1, 127, $length);
		return $header.$text;
	}

	// Handshake new client.
	function handshake($receivedHeader, $clientConnection, $host, $port, $uri = '/ws') {
		$headers = array();
		$lines = preg_split("/\r\n/", $receivedHeader);
		foreach($lines as $line) {
			$line = chop($line);
			if(preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {
				$headers[$matches[1]] = $matches[2];
			}
		}

		$secKey = $headers['Sec-WebSocket-Key'];
		$secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
		//hand shaking header
		$upgrade  = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
                "Upgrade: websocket\r\n" .
                "Connection: Upgrade\r\n" .
                "WebSocket-Origin: $host\r\n" .
                "WebSocket-Location: ws://$host:$port$uri\r\n".
                "Sec-WebSocket-Accept: $secAccept\r\n\r\n";
		socket_write($clientConnection, $upgrade, strlen($upgrade));
	}