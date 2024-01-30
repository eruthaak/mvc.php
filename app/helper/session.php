<?php

/**
 * Checks session array with specified key, if doesn't exists, it creates.
 * @author Beresyus
 */
function session(mixed $key, mixed $value = null) {
  if( isset($value) ) $_SESSION[$key] = $value;
  if( isset( $_SESSION[$key] ) ) return $_SESSION[$key];
  return false;
}

/**
 * Removes a value from session specified key.
 * @author Beresyus
 */
function sessionKill(mixed $key) {
  unset($_SESSION[$key]);
}