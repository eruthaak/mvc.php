<?php

/**
 * Get controller file via. name.
 * @author Beresyus
 */
function controller($name) {
  return CONTROLLER . '/' . $name . '.php';
}

/**
 * Get view file via. name.
 * @author Beresyus
 */
function view($name) {
  return VIEW . '/' . $name . '.php';
}

/**
 * Get image file via. name.
 * @author Beresyus
 */
function img($name) {
  return ASSETS . '/' . $name;
}

/**
 * Get upload file via. name.
 * @author Beresyus
 */
function uploads($name) {
  return UPLOADS . '/' . $name;
}

/**
 * Get website URL with specified url variable.
 * @author Beresyus
 */
function site($url = null) {
  return APP_URL . '/' . $url;
}

/**
 * Get assets via. domain with specified file path.
 * @author Beresyus
 */
function asset($url = null) {
  return APP_URL . '/assets/' . $url;
}





  