<?php

/**
 * This clears request values for specialchars of HTML.
 * @param $str : Data.
 * @author Beresyus
 */
function filterUrl($str) {
  return htmlspecialchars(trim($str));
}

/**
 * SECURED - This is filtering for specialchars of HTML where is sent by GET request.
 * @param $name : Request key.
 * @author Beresyus
 */
function get($name) {
  if(isset($_GET[$name])) {
    if(is_array($_GET[$name])) {
      return array_map(function($item) {
        return filterUrl($item);
      }, $_GET[$name]);
    }
    return filterUrl($_GET[$name]);
  }
  return false;
}

/**
 * SECURED - This is filtering for specialchars of HTML where is sent by POST request.
 * @param $name : Request key.
 * @author Beresyus
 */
function post($name) {
  if(isset($_POST[$name])) {
    if(is_array($_POST[$name])) {
      return array_map(function($item) {
        return filterUrl($item);
      }, $_POST[$name]);
    }
    return filterUrl($_POST[$name]);
  }
  return false;
}

/**
 * UNSECURED - This is not filtering for specialchars of HTML.
 * @param $name : Request key.
 * @author Beresyus
 */
function request($name) {
  if(isset($_POST[$name])) {
    if(is_array($_POST[$name])) {
      return array_map(function($item) {
        return $item;
      }, $_POST[$name]);
    }
    return $_POST[$name];
  }
  if(isset($_GET[$name])) {
    if(is_array($_GET[$name])) {
      return array_map(function($item) {
        return $item;
      }, $_GET[$name]);
    }
    return $_GET[$name];
  }
  return false;
}

/**
 * Gets request headers from sent request.
 * @author Beresyus
 */
function requestHeaders($name) {
  $header = 'HTTP_' . strtoupper($name);
  $header = server($header);
  if($header) return $header;
  return false;
}

/**
 * Prevent Cross-Site Request Forgery
 * @author Beresyus
 */
function csrf() {
  global $csrfToken;
  return '<input type="hidden" name="csrf" value="' . $csrfToken . '">';
}

/**
 * Use specified HTTP method on a form.
 * @author Beresyus
 */
function method($method = NULL) {
  if(isset($method)) {
    return '<input type="hidden" name="_method" value="' . $method . '">';
  } else {
    return server('REQUEST_METHOD', post('_method') ? post('_method') : 'GET');
  }
}