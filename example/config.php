<?php

define('ROOT_DIR', dirname(dirname(__FILE__)));
define('DOWNLOADS_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'downloads');
define('CONNECTIONS_LIMIT', 8);

ini_set('display_errors', 'on');
error_reporting(E_ALL);


require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'vendor' .DIRECTORY_SEPARATOR . 'autoload.php';
