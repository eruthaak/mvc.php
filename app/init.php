<?php
  spl_autoload_register(
    function ($className) {
      $classFile = realpath('.') . '/app/classes/class.' . strtolower($className) . '.php';
      if(file_exists($classFile)) {
        require $classFile;
      }
    }
  ); // Class files loading.

  Helper::Load(); // Helpers loading.

  require 'system/config.php'; // Settings.
  $db = new Database(DB_CREDENTIALS, DB_USERNAME, DB_PASSWORD); // Database connection.