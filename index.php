<?php
  require 'app/init.php';

  $_ROUTE = get(0);
  $_ROUTE = array_filter(explode('/', $_ROUTE));

  // if( !route(0) ) route(0, 'tr');
  if( route(0) === 'rss' ) require controller('rss');
  if( route(0) === 'api' ) require controller('api');
  // if( file_exists(DIR . '/app/language/'.route(0).'.php') ) require DIR . '/app/language/' . route(0) . '.php'; // Language File

  if( !route(0) ) route(0, 'index');

  if( !file_exists( controller( route(0) ) ) ) abort(404);

  // Session
  session_start();
  $csrfToken = sha1(md5(rand(1, 999999)));


  if( !route(1) ) route(1, 'index');
  require controller( route(1) );
  session('csrf-token', $csrfToken);