<?php

define('ROOT_DIR', dirname(dirname(__FILE__)));
define('DOWNLOADS_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'downloads');
define('CONNECTIONS_LIMIT', 8);

function autoloadClasses($class) {
	$filePath = ROOT_DIR . DIRECTORY_SEPARATOR . str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';
	if(is_file($filePath)) {
		return require_once ($filePath);
	}
	$filePath = str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';
	if(is_file($filePath)) {
		return require_once ($filePath);
	}
}
spl_autoload_register('autoloadClasses');

ini_set('display_errors', 'on');
error_reporting(E_ALL);
