<?php
define('DIR', realpath('.'));
define('CONTROLLER', DIR . '/app/controller');
define('VIEW', DIR . '/app/view');
define('ASSETS', DIR . '/assets/img');
define('UPLOADS', DIR . '/uploads');
const APP_CREATOR = 'Beresyus';
const ENCRYPTION_CIPHER = 'AES-128-CBC';
const ENCRYPTION_KEY = 'berestest.104884';

const APP_TIMEZONE = 'Europe/Istanbul';
date_default_timezone_set(APP_TIMEZONE);
const APP_DEBUG = true;
const APP_URL = 'https://project.test';

const DB_HOST = 'localhost';
const DB_PORT = '3306';
const DB_NAME = 'test';
const DB_CHARSET = 'utf8';
const DB_USERNAME = 'root';
const DB_PASSWORD = NULL;
const DB_CREDENTIALS = 'mysql:dbname='.DB_NAME.';host='.DB_HOST.';port='.DB_PORT.';charset='.DB_CHARSET;

const APP_NAME = '';