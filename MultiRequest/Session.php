<?php

/**
 * @see https://github.com/barbushin/multirequest
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 *
 */
class MultiRequest_Session {
	
	/**
	 * @var MultiRequest_RequestsDefaults
	 */
	protected $requestsDefaults;
	
	/**
	 * @var MultiRequest_Callbacks
	 */
	protected $callbacks;
	
	protected $mrHandler;
	protected $cookiesFilepath;
	protected $lastRequest;
	protected $enableAutoStart;
	protected $enableAutoReferer;
	protected $requestsDelay;

	public function __construct(MultiRequest_Handler $mrHandler, $cookiesBasedir, $enableAutoReferer = false, $requestsDelay = 0) {
		$this->callbacks = new MultiRequest_Callbacks();
		$this->mrHandler = $mrHandler;
		$this->enableAutoReferer = $enableAutoReferer;
		$this->requestsDelay = $requestsDelay;
		$this->requestsDefaults = new MultiRequest_Defaults();
		$this->cookiesFilepath = tempnam($cookiesBasedir, '_');
	}

	/**
	 * @return MultiRequest_Handler
	 */
	public function getMrHandler() {
		return $this->mrHandler;
	}

	public function buildRequest($url) {
		$request = new MultiRequest_Request($url);
		$request->_session = $this;
		return $request;
	}

	/**
	 * @return MultiRequest_Request
	 */
	public function requestsDefaults() {
		return $this->requestsDefaults;
	}

	public function onRequestComplete($callback) {
		$this->callbacks->add(__FUNCTION__, $callback);
		return $this;
	}

	public function notifyRequestIsComplete(MultiRequest_Request $request, MultiRequest_Handler $mrHandler) {
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

	public function request(MultiRequest_Request $request) {
		if($this->requestsDelay) {
			sleep($this->requestsDelay);
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

