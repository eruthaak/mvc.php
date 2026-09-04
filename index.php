<?php
  require 'app/init.php';

  $_ROUTE = get(0);
  $_ROUTE = array_filter(explode('/', $_ROUTE));
  
  // Session
  session_start();
  $csrfToken = sha1(md5(rand(1, 999999)));

  // if( route(0) === 'rss' ) require controller('rss'); // If you have RSS feed, activate this line.
  // if( route(0) === 'api' ) require controller('api'); // If you have API, activate this line.
  // if( route(0) === 'sse' ) require controller('sse'); // If you have event-stream event, activate this line.

  if( !route(0) ) route(0, 'index');

  if( !file_exists( controller( route(0) ) ) ) abort(404);

  require controller( route(0) );
  session('csrfToken', $csrfToken);