<?php
  class HTTP {
    public static function GET(string $source, array|string $data = null, $headers = null) {
      $ch = curl_init($source);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      if(isset($data)) {
        if( gettype($data) === 'string' ) {
          curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
          curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        } else {
          curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
      }
      if(isset($headers)) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      $response = curl_exec($ch);
      curl_close($ch);
      return $response;
    }

    public static function POST(string $source, array|string $data, $headers = null) {
      $ch = curl_init($source);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_POST, true);
      if( gettype($data) === 'string' ) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
      } else {
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
      }
      if(isset($headers)) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      $response = curl_exec($ch);
      curl_close($ch);
      return $response;
    }
  }