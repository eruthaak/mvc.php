<?php

  /**
   * Get a svg photo on server-side.
   */
  function svg($name, float $height = null, float $width = null, float $x = 0.00, float $y = 0.00) {
    $content = file_get_contents( ASSETS . '/svg/' . $name );

    if( isset($height) ) $content = str_replace('svg ', 'svg height="' . $height . '" ', $content);
    if( isset($width) ) $content = str_replace('svg ', 'svg width="' . $width . '" ', $content);

    $content = preg_replace('/viewBox="\K0.00 0.00/', $x . ' ' . $y, $content, 1);

    return $content;
  }