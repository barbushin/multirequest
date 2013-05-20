<?php
namespace MultiRequest;


/**
 * @see https://github.com/barbushin/multirequest
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 *
 */
class Session {
	
	/**
	 * @var RequestsDefaults
	 */
	protected $requestsDefaults;
	
	/**
	 * @var Callbacks
	 */
	protected $callbacks;
	
	protected $mrHandler;
	protected $cookiesFilepath;
	protected $lastRequest;
	protected $enableAutoStart;
	protected $enableAutoReferer;
	protected $requestsDelay;

	public function __construct(Handler $mrHandler, $cookiesBasedir, $enableAutoReferer = false, $requestsDelay = 0) {
		$this->callbacks = new Callbacks();
		$this->mrHandler = $mrHandler;
		$this->enableAutoReferer = $enableAutoReferer;
		$this->requestsDelay = $requestsDelay;
		$this->requestsDefaults = new Defaults();
		$this->cookiesFilepath = tempnam($cookiesBasedir, '_');
	}

	/**
	 * @return Handler
	 */
	public function getMrHandler() {
		return $this->mrHandler;
	}

	public function buildRequest($url) {
		$request = new Request($url);
		$request->_session = $this;
		return $request;
	}

	/**
	 * @return Request
	 */
	public function requestsDefaults() {
		return $this->requestsDefaults;
	}

	public function onRequestComplete($callback) {
		$this->callbacks->add(__FUNCTION__, $callback);
		return $this;
	}

	public function notifyRequestIsComplete(Request $request, Handler $mrHandler) {
		$this->lastRequest = $request;
		$this->callbacks->onRequestComplete($request, $this, $mrHandler);
	}

	public function start() {
		$this->enableAutoStart = true;
		$this->mrHandler->start();
	}

	public function stop() {
		$this->enableAutoStart = false;
	}
	
 	public function setRequestingDelay($milliseconds) {
        	$this->requestingDelay = $milliseconds;
    	}
    	
	public function request(Request $request) {
		if($this->requestsDelay) {
			usleep($this->requestsDelay);
		}
		$request->onComplete(array($this, 'notifyRequestIsComplete'));
		
		$this->requestsDefaults->applyToRequest($request);
		$request->setCookiesStorage($this->cookiesFilepath);
		if($this->enableAutoReferer && $this->lastRequest) {
			$request->setCurlOption(CURLOPT_REFERER, $this->lastRequest->getUrl());
		}
		
		$this->mrHandler->pushRequestToQueue($request);
		if($this->enableAutoStart) {
			$this->mrHandler->start();
		}
	}

	public function clearCookie() {
		if(file_exists($this->cookiesFilepath)) {
			unlink($this->cookiesFilepath);
		}
	}

	public function __destruct() {
		$this->clearCookie();
	}
}

