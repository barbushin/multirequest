<?php
require_once ('config.php');


use \MultiRequest\Request;
use \MultiRequest\Handler;

/***************************************************************
  DEBUG METHODS
 **************************************************************/

function debug($message) {
	echo $message . '<br />' . "\n";
	flush();
}

function debugRequestComplete(Request $request, Handler $handler) {
	debug('Request complete: ' . $request->getUrl() . ' Code: ' . $request->getCode() . ' Time: ' . $request->getTime());
	debug('Requests in waiting queue: ' . $handler->getRequestsInQueueCount());
	debug('Active requests: ' . $handler->getActiveRequestsCount());
}

function saveCompleteRequestToFile(Request $request, Handler $handler) {
	$filename = preg_replace('/[^\w\.]/', '', $request->getUrl());
	file_put_contents(DOWNLOADS_DIR . DIRECTORY_SEPARATOR . $filename, $request->getContent());
}

function prepareDownloadsDir() {
	$dirPath = DOWNLOADS_DIR;
	chmod($dirPath, 0777);
	$dirIterator = new \RecursiveDirectoryIterator($dirPath);
	$recursiveIterator = new \RecursiveIteratorIterator($dirIterator);
	foreach($recursiveIterator as $path) {
		if($path->isFile() && strpos($path->getFilename(), '.')) {
			unlink($path->getPathname());
		}
	}
}
//prepareDownloadsDir(DOWNLOADS_DIR);

/***************************************************************
  MULTIREQUEST INIT
 **************************************************************/


$mrHandler = new Handler();
$mrHandler->setConnectionsLimit(CONNECTIONS_LIMIT);
$mrHandler->onRequestComplete('debugRequestComplete');
$mrHandler->onRequestComplete('saveCompleteRequestToFile');

$headers = array();
$headers[] = 'Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5';
$headers[] = 'Cache-Control: no-cache';
$headers[] = 'Connection: Keep-Alive';
$headers[] = 'Keep-Alive: 300';
$headers[] = 'Accept-Charset: UTF-8,Windows-1251,ISO-8859-1;q=0.7,*;q=0.7';
$headers[] = 'Accept-Language: ru,en-us,en;q=0.5';
$headers[] = 'Pragma:';
$mrHandler->requestsDefaults()->addHeaders($headers);

$options = array();
$options[CURLOPT_USERAGENT] = 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12';
$mrHandler->requestsDefaults()->addCurlOptions($options);

$urls = array('http://forums.somethingawful.com/', 'http://asdlksda.sas', 'http://www.somethingpositive.net/', 'http://www.somethingawful.com/', 'http://awesome-hd.net/', 'http://www.istartedsomething.com/', 'http://www.somewhere.fr/', 'http://forums.tkasomething.com/', 'http://www.somewhereinblog.net/', 'http://www.killsometime.com/', 'http://v.sometrics.com/', 'http://www.fearsome-oekaki.com/', 'http://www.dosomething.org/', 'http://www.avonandsomerset.police.uk/');
foreach($urls as $url) {
	$request = new Request($url);
	$mrHandler->pushRequestToQueue($request);
}

$startTime = time();

set_time_limit(300);
$mrHandler->start();

debug('Total time: ' . (time() - $startTime));