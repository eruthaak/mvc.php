<?php

/**
 * Gets a value from $_SERVER global array.
 * @author Beresyus
 */
function server($key, $value = NULL) {
  if( isset($value) ) $_SERVER[strtoupper($key)] = $value;
  if( isset($_SERVER[strtoupper($key)]) ) return $_SERVER[strtoupper($key)];
  return false;
}