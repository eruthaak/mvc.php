<?php

class WebSocket_Client {
  protected $connection;

  public function __construct(string $host = '127.0.0.1', int $port = 9000, $headers = [], &$error_string = '', int $timeout = 10, bool $ssl = false, bool $persistant = false, string $path = '/', $context = null) {

    // Generate a key (to convince server that the update is not random)
    // The key is for the server to prove it is websocket aware. (We know it is)
    $key = base64_encode(openssl_random_pseudo_bytes(16));
    
    $header = array(
      "GET $path HTTP/1.1",
      "Host: $host:$port",
      "User-Agent: beresyus/php-websocket-client",
      "Upgrade: websocket",
      "Connection: Upgrade",
      "Sec-WebSocket-Key: $key",
      "Sec-WebSocket-Version: 13"
    );

    // Add extra headers
    $header = array_merge($header, $headers);
    $header = implode("\r\n", $header);
    // Add end of header marker
    $header .= "\r\n";

    // Connect to server
    $port = $port < 1 ? ($ssl ? 443 : 80) : $port;
    $address = ($ssl ? 'ssl://' : '') . $host . ':' . $port;

    $flags = STREAM_CLIENT_CONNECT | ($persistant ? STREAM_CLIENT_PERSISTENT : 0);
    $ctx = $context ?? stream_context_create();

    // the '@' to silent the Warning Error. Don't get mad, errors are handled below with Exception
    $sp = @stream_socket_client($address, $errno, $errstr, $timeout, $flags, $ctx);

    if ($sp === false) {
      $error_string = "Unable to connect to websocket server: $errstr ($errno)";
      throw new Exception($error_string);
    }

    // Set timeouts
    stream_set_timeout($sp, $timeout);

    if (!$persistant or ftell($sp) === 0) {

      // Request upgrade to websocket
      $rc = fwrite($sp, $header);
      if (!$rc) {
        $error_string = "Unable to send upgrade header to websocket server: $errstr ($errno)";
        throw new Exception($error_string);
      }

      // Read response into an assotiative array of headers. Fails if upgrade failes.
      $responseHeader = fread($sp, 1024);

      // Status code 101 indicates that the WebSocket handshake has completed.
      if (stripos($responseHeader, ' 101 ') === false || stripos($responseHeader, 'Sec-WebSocket-Accept: ') === false) {
        $error_string = "Server did not accept to upgrade connection to websocket." . $responseHeader . E_USER_ERROR;
        throw new Exception($error_string);
      }
      // The key we send is returned, concatenate with "258EAFA5-E914-47DA-95CA-
      // C5AB0DC85B11" and then base64-encoded. one can verify if one feels the need...
    }

    $this->connection = $sp;
  }

  public function send($data, bool $final = true, bool $binary = true) {
    // Assemble header: FINal 0x80 | Mode (0x02 binary, 0x01 text)
    if ($binary) {
      $header = chr(($final ? 0x80 : 0) | 0x02); // 0x02 binary mode
    } else {
      $header = chr(($final ? 0x80 : 0) | 0x01); // 0x01 text mode
    }

    /* Mask 0x80 | payload length (0-125) */
    if (strlen($data) < 126)
      $header .= chr(0x80 | strlen($data));
    elseif (strlen($data) < 0xFFFF)
      $header .= chr(0x80 | 126) . pack("n", strlen($data));
    else
      $header .= chr(0x80 | 127) . pack("N", 0) . pack("N", strlen($data));

    // Add mask
    $mask = pack("N", rand(1, 0x7FFFFFFF));
    $header .= $mask;

    // Mask application data.
    for ($i = 0; $i < strlen($data); $i++)
      $data[$i] = chr(ord($data[$i]) ^ ord($mask[$i % 4]));

    $written = fwrite($this->connection, $header . $data);
    if (false === $written) {
      throw new Exception('Unable to write to websocket');
    }

    return $written;
  }

  public function get(&$error_string = NULL) {
    $data = "";

    do {
      // Read header
      $header = fread($this->connection, 2);
      if (!$header) {
        $error_string = "Reading header from websocket failed.";
        throw new Exception($error_string);
      }

      $opcode = ord($header[0]) & 0x0F;
      $final = ord($header[0]) & 0x80;
      $masked = ord($header[1]) & 0x80;
      $payload_len = ord($header[1]) & 0x7F;

      // Get payload length extensions
      $ext_len = 0;
      if ($payload_len >= 0x7E) {
        $ext_len = 2;
        if ($payload_len == 0x7F)
          $ext_len = 8;
        $header = fread($this->connection, $ext_len);
        if (!$header) {
          $error_string = "Reading header extension from websocket failed.";
          throw new Exception($error_string);
        }

        // Set extented paylod length
        $payload_len = 0;
        for ($i = 0; $i < $ext_len; $i++)
          $payload_len += ord($header[$i]) << ($ext_len - $i - 1) * 8;
      }

      // Get Mask key
      if ($masked) {
        $mask = fread($this->connection, 4);
        if (!$mask) {
          $error_string = "Reading header mask from websocket failed.";
          throw new Exception($error_string);
        }
      }

      // Get payload
      $frame_data = '';
      while ($payload_len > 0) {
        $frame = fread($this->connection, $payload_len);
        if (!$frame) {
          $error_string = "Reading payload from websocket failed.";
          throw new Exception($error_string);
        }
        $payload_len -= strlen($frame);
        $frame_data .= $frame;
      }

      // Handle ping requests (sort of) send pong and continue to read
      if ($opcode == 9) {
        // Assamble header: FINal 0x80 | Opcode 0x0A + Mask on 0x80 with zero payload
        fwrite($this->connection, chr(0x8A) . chr(0x80) . pack("N", rand(1, 0x7FFFFFFF)));
        continue;

        // Close
      } elseif ($opcode == 8) {
        fclose($this->connection);

        // 0 = continuation frame, 1 = text frame, 2 = binary frame
      } elseif ($opcode < 3) {
        // Unmask data
        $data_len = strlen($frame_data);
        if ($masked)
          for ($i = 0; $i < $data_len; $i++)
            $data .= $frame_data[$i] ^ $mask[$i % 4];
        else
          $data .= $frame_data;
      } else
        continue;
    } while (!$final);

    return $data;
  }
}