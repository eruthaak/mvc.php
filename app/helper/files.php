<?php
  
/**
 * You can catch files if files uploaded to server.
 * @author Beresyus
 */
function files($name) {
  if(isset($_FILES[$name])) {
    if(is_array($_FILES[$name])) {
      return (object) array_map(function($item) {
        return $item;
      }, $_FILES[$name]);
    }
    return $_FILES[$name];
  }
  return false;
}

/**
 * Get file extension if there is a file.
 * @author Beresyus
 */
function extension($name){
  return pathinfo($name)['extension'];
}