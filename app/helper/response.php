<?php

/**
 * This function redirects you.
 * @param string $href : Redirection URL.
 */
function redirect(string $href) {
  header("Location: $href");
}

/**
 * This function redirects you after specified seconds.
 * @param int $time : Seconds.
 * @param string $href : Redirection URL
 */
function redirectAfter(int $time, string $href) {
  header("Refresh: $time; URL=$href");
}

/**
 * This function exits from your application by looking HTTP status code.
 * @param int $code : HTTP status code.
 */
function abort(int $code) {
  $_RESPONSE["CODES"] = array(
    400 => "Bad Request",
    401 => "Unauthorized",
    403 => "Forbidden",
    404 => "Not Found",
    410 => "Gone",
    411 => "Length Required",
    429 => "Too Many Requests"
  );
  $filtered = array_filter($_RESPONSE["CODES"], function($key) use ($code) { return $key === $code; }, ARRAY_FILTER_USE_KEY);
  $code = array_key_first($filtered);
  $message = $filtered[$code];
  header("HTTP/1.1 $code $message", true, $code);
  @include view('error');
  exit;
}