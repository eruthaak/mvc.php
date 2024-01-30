<?php

/**
 * Gets a value from $_SERVER global array.
 * @author Beresyus
 */
function server(mixed $key) {
  if( isset($_SERVER[$key]) ) return $_SERVER[$key];
  return false;
}