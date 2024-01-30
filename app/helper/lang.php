<?php

/**
 * This method returns converted string if your project have a language file under /app/language folder.
 * @author Beresyus
 */
function __(string $langCode) {
  global $lang;
  if( isset($lang[strtolower($langCode)]) ) return $lang[strtolower($langCode)];
  return $langCode;
}