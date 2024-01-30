<?php
  class MySocket {
    private Socket $socket;
    private string $host = 'localhost', $port = '9000';
    private array $clients = array();
    private NULL $null = NULL;

    /**
     * 
     */
    public function __construct(string $host = 'localhost', string $port = '9000') { 
      $this->host = $host; $this->port = $port;
      $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP); // Create TCP/IP sream socket
    }

    /**
     * Start endless loop, so that our script doesn't stop.
     */
    public function serve() {
      socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1); // Reuseable port
      socket_bind($this->socket, $this->host, $this->port); // Bind socket to specified host
      socket_listen($this->socket); // Listen to port
      $this->clients = array($this->socket); // Create & add listening socket to the list
      while (true) {
        $changed = $this->clients; // Manage multiple connections
        
        socket_select($changed, $this->null, $this->null, 0, 10); // Returns the socket resources in $changed array

        if (in_array($this->socket, $changed)) /* Check for new socket */ {
          $newSocket = socket_accept($this->socket); // Accept new socket
          $clients[] = $newSocket; // Add socket to client array

          $header = socket_read($newSocket, 1024); // Read data sent by the socket
          $this->handshake($header, $newSocket, $this->host, $this->port); // Perform websocket handshake

          socket_getpeername($newSocket, $ip); // Get IP address of connected socket.
          // $response = mask(json_encode(array('type'=>'system', 'message'=>$ip.' connected'))); // Prepare json data
          // $this->send($response); // Notify all users about new connection
          
          $found_socket = array_search($this->socket, $changed); // Make room for new socket
          unset($changed[$found_socket]);
        }

        foreach ($changed as $changedSocket) { // Loop through all connected sockets
          while(socket_recv($changedSocket, $buf, 1024, 0) >= 1) { // Check for any incomming data
            $received_text = $this->unmask($buf); // Unmask data
            $data = json_decode($received_text, true); // Json decode

            $response_text = $this->mask(json_encode($data)); // Prepare data to be sent to client
            $this->send($response_text); // Send data
            break 2; // Exist this loop
          }

          $buf = @socket_read($changedSocket, 1024, PHP_NORMAL_READ);
          if ($buf === false) { // Check disconnected client
            // Remove client for $clients array
            $foundSocket = array_search($changedSocket, $clients);
            socket_getpeername($changedSocket, $ip);
            unset($clients[$foundSocket]);

            // Notify all users about disconnected connection
      
            // $response = mask(json_encode(array('type'=>'system', 'message'=>$ip.' disconnected')));
            // $this->send($response);
          }
        }
      }
    }

    public function connect($uri = '/ws', $host = 'localhost', $port = '9000') {
      if(@socket_connect($this->socket, $host, $port)) {
        // Makes WebSocket Handshake
        $handshakeHeaders =  
          "GET $uri HTTP/1.1\r\n" .
          "Host: $host\r\n" .
          "Port: $port\r\n" .
          "Upgrade: websocket\r\n" .
          "Connection: Upgrade\r\n" .
          "Sec-WebSocket-Key: " . base64_encode(openssl_random_pseudo_bytes(16)) . "\r\n" .
          "Sec-WebSocket-Version: 13\r\n\r\n";

        socket_write($this->socket, $handshakeHeaders, strlen($handshakeHeaders));
        $response = socket_read($this->socket, 2048);

        $this->clients = array($this->socket); // Create & add listening socket to the list
        return $response;
      }
      return false;
    }

    /**
     * Send your data.
     */
    public function send($msg) {
      foreach($this->clients as $changedSocket) {
        @socket_write($changedSocket, $msg, strlen($msg));
      }
      return true;
    }

    /**
     * Encode message for transfer to client.
     */
    public function mask($text) {
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

    /**
     * Unmask incoming framed message.
     */
    public function unmask($text) {
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

    /**
     * Handshake new client.
     */
    private function handshake(string $receivedHeaders, $clientConnection, $host, $port) {
      $headers = array();
      $lines = preg_split("/\r\n/", $receivedHeaders);
      foreach($lines as $line)
      {
        $line = chop($line);
        if(preg_match('/\A(\S+): (.*)\z/', $line, $matches))
        {
          $headers[$matches[1]] = $matches[2];
        }
      }

      $secKey = $headers['Sec-WebSocket-Key'];
      $secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));

      $upgrade  = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
                  "Upgrade: websocket\r\n" .
                  "Connection: Upgrade\r\n" .
                  "WebSocket-Origin: $host\r\n" .
                  "WebSocket-Location: ws://$host:$port/demo/shout.php\r\n".
                  "Sec-WebSocket-Accept:$secAccept\r\n\r\n"; // Hand Shaking Header
      socket_write($clientConnection, $upgrade, strlen($upgrade));
    }

    /**
     * Closes the socket connection.
     */
    public function __destruct() {
      socket_close($this->socket);
    }
  }