<?php

ini_set('display_errors', 'on');
error_reporting(E_ALL);
set_time_limit(0);

require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'vendor' .DIRECTORY_SEPARATOR . 'autoload.php';

use \MultiRequest\Request;
use \MultiRequest\Handler;

/***************************************************************
  DEBUG METHODS
 **************************************************************/

function debug($message) {
	echo $message . '<br />' . "\n";
	flush();
}

function getDownloadsDir() {
    return dirname(__FILE__) . DIRECTORY_SEPARATOR . 'downloads';
}

function debugRequestComplete(Request $request, Handler $handler) {
	debug('Request complete: ' . $request->getUrl() . ' Code: ' . $request->getCode() . ' Time: ' . $request->getTime());
	debug('Requests in waiting queue: ' . $handler->getRequestsInQueueCount());
	debug('Active requests: ' . $handler->getActiveRequestsCount());
}

function saveCompleteRequestToFile(Request $request, Handler $handler) {
    var_dump($request->getRequestHeaders());

    var_dump($request->getResponseHeaders());
	$filename = preg_replace('/[^\w\.]/', '', $request->getUrl());

	file_put_contents(getDownloadsDir() . DIRECTORY_SEPARATOR . $filename, $request->getContent());
}

function prepareDownloadsDir($dirPath) {
	chmod($dirPath, 0777);

	$dirIterator = new \RecursiveDirectoryIterator($dirPath);
	$recursiveIterator = new \RecursiveIteratorIterator($dirIterator);

	foreach($recursiveIterator as $path) {
		if($path->isFile() && strpos($path->getFilename(), '.')) {
			unlink($path->getPathname());
		}
	}
}

prepareDownloadsDir(getDownloadsDir());

/***************************************************************
  MULTIREQUEST INIT
 **************************************************************/
$urls = array(
    'http://www.yandex.ru/',
    'http://www.yandex.ru/',
    'http://www.yandex.ru/',
    'http://www.yandex.ru/',
    'http://www.yandex.ru/',
    'http://www.yandex.ru/',
    'http://www.yandex.ru/',
    'http://www.yandex.ru/',
    'http://www.yandex.ru/',
    'http://www.yandex.ru/',
    'http://www.yandex.ru/',
    'http://www.yandex.ru/',
    'http://www.yandex.ru/',
    'http://www.yandex.ru/',
    'http://www.yandex.ru/',
    'http://www.yandex.ru/',
    'http://www.yandex.ru/',
    'http://www.yandex.ru/',
    'http://www.yandex.ru/',
    'http://www.yandex.ru/',
    'http://www.yandex.ru/',
    'http://www.yandex.ru/',
    'http://www.yandex.ru/',
    'http://www.yandex.ru/',
);

$headers = array(
    'Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5',
    'Cache-Control: no-cache',
    'Connection: Keep-Alive',
    'Keep-Alive: 300',
    'Accept-Charset: UTF-8,Windows-1251,ISO-8859-1;q=0.7,*;q=0.7',
    'Accept-Language: ru,en-us,en;q=0.5',
    'Pragma:',
);

$curlOptions = array(
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12',
    CURLOPT_PROXY => '95.79.55.210:3128',
    CURLOPT_CONNECTTIMEOUT_MS => 5000
);

$mrHandler = new Handler();

$mrHandler->setConnectionsLimit(1000);

$mrHandler->onRequestComplete('debugRequestComplete');
$mrHandler->onRequestComplete('saveCompleteRequestToFile');

$mrHandler->requestsDefaults()->addHeaders($headers);

$mrHandler->requestsDefaults()->addCurlOptions($curlOptions);

$Session = new \MultiRequest\Session($mrHandler, '/tmp');
$Session->start();

foreach($urls as $url) {
	$request = new Request($url);
    $request->addCurlOptions($curlOptions);

    $Session->request($request);
	//$mrHandler->pushRequestToQueue($request);
}

$startTime = time();

$mrHandler->start();

debug('Total time: ' . (time() - $startTime));





