<?php

/**
 * Get a route by index.
 * @author Beresyus
 */
function route($index, string $url = null) {
  global $_ROUTE;
  if( isset( $url ) ) $_ROUTE[$index] = $url;
  if( isset( $_ROUTE[$index] ) ) return $_ROUTE[$index];
  return false;
}

/**
 * Encode URL letters.
 * @author Beresyus
 */
function encode($title) {
  $title = trim($title);
  $trans = array("Ğ" => "g", "Ü" => "u", "Ş" => "s", "I" => "i", "İ" => "i", "Ö" => "o", "Ç" => "c", "ğ" => "g", "ü" => "u", "ş" => "s", "ı" => "i", "ö" => "o", "ç" => "c", " " => "-");
  $title = strtr($title, $trans);
  $title = preg_replace('/[^A-Za-z0-9\-]/', '', $title);
  $title = strtolower($title);
  return $title;
}