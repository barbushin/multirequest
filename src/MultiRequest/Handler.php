<?php
namespace MultiRequest;


/**
 * @see https://github.com/barbushin/multirequest
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 *
 */
class Handler {

	/** @var Defaults */
	protected $requestsDefaults;
	/** @var Callbacks */
	protected $callbacks;
	/** @var Queue */
	protected $queue;
    /** @var Request[] */
	protected $activeRequests = array();

	protected $connectionsLimit = 60;
	protected $totalBytesTransferred;
	protected $isActive;
	protected $isStarted;
	protected $isStopped;
	protected $requestingDelay = 0.01;

	public function __construct(Queue $queue = null, Defaults $defaults = null, Callbacks $callbacks = null) {
		if(!extension_loaded('curl')) {
			throw new Exception('CURL extension must be installed and enabled');
		}
		$this->queue = $queue ? : new Queue();
		$this->requestsDefaults = $defaults ? : new Defaults();
		$this->callbacks = $callbacks ? : new Callbacks();
	}

	public function getQueue() {
		return $this->queue;
	}

	public function setRequestingDelay($milliseconds) {
		$this->requestingDelay = $milliseconds / 1000;
	}

	public function onRequestComplete($callback) {
		$this->callbacks->add(__FUNCTION__, $callback);
		return $this;
	}

	protected function notifyRequestComplete(Request $request) {
		$request->notifyIsComplete($this);
        /** @noinspection PhpUndefinedMethodInspection */
        $this->callbacks->onRequestComplete($request, $this);
	}

	/**
	 * @return Request
	 */
	public function requestsDefaults() {
		return $this->requestsDefaults;
	}

	public function isActive() {
		return $this->isActive;
	}

	public function isStarted() {
		return $this->isStarted;
	}

	public function setConnectionsLimit($connectionsCount) {
		$this->connectionsLimit = $connectionsCount;
	}

	public function getRequestsInQueueCount() {
		return $this->queue->count();
	}

	public function getActiveRequestsCount() {
		return count($this->activeRequests);
	}

	public function stop() {
		$this->isStopped = true;
	}

	public function activate() {
		$this->isStopped = false;
		$this->start();
	}

	public function pushRequestToQueue(Request $request) {
		$this->queue->push($request);
	}

	protected function sendRequestToMultiCurl($mcurlHandle, Request $request) {
		$this->requestsDefaults->applyToRequest($request);
		curl_multi_add_handle($mcurlHandle, $request->getCurlHandle(true));
	}

	public function start() {
		if($this->isActive || $this->isStopped) {
			return;
		}
		$this->isActive = true;
		$this->isStarted = true;
        $curlHandle = curl_multi_init();

		try {
			do {

				// send requests from queue to CURL
				if(count($this->activeRequests) < $this->connectionsLimit) {
					for($i = $this->connectionsLimit - count($this->activeRequests); $i > 0; $i--) {
						$request = $this->queue->pop();
						if($request) {
							$this->sendRequestToMultiCurl($curlHandle, $request);
							$this->activeRequests[$request->getId()] = $request;
						}
						else {
							break;
						}
					}
				}

				while(CURLM_CALL_MULTI_PERFORM === curl_multi_exec($curlHandle, $activeThreads)) {
					;
				}

				// check complete requests
				curl_multi_select($curlHandle, $this->requestingDelay);
				while($completeCurlInfo = curl_multi_info_read($curlHandle)) {
					$completeRequestId = Request::getRequestIdByCurlHandle($completeCurlInfo['handle']);
					$completeRequest = $this->activeRequests[$completeRequestId];
					unset($this->activeRequests[$completeRequestId]);
					curl_multi_remove_handle($curlHandle, $completeRequest->getCurlHandle());
					$completeRequest->handleCurlResult();

					// check if response code is 301 or 302 and follow location
					$ignoreNotification = false;
					$completeRequestCode = $completeRequest->getCode();

					if($completeRequestCode == 301 || $completeRequestCode == 302) {
						$completeRequestOptions = $completeRequest->getCurlOptions();
						if(!empty($completeRequestOptions[CURLOPT_FOLLOWLOCATION])) {
							$completeRequest->_permanentlyMoved = empty($completeRequest->_permanentlyMoved) ? 1 : $completeRequest->_permanentlyMoved + 1;
							$responseHeaders = $completeRequest->getResponseHeaders(true);
							if($completeRequest->_permanentlyMoved < 5 && !empty($responseHeaders['Location'])) {
								// figure out whether we're dealing with an absolute or relative redirect (thanks to kmontag https://github.com/kmontag for this bugfix)
								$redirectedUrl = (parse_url($responseHeaders['Location'], PHP_URL_SCHEME) === null ? $completeRequest->getBaseUrl() : '') . $responseHeaders['Location'];
								$completeRequest->setUrl($redirectedUrl);
								$completeRequest->reInitCurlHandle();
								$this->pushRequestToQueue($completeRequest);
								$ignoreNotification = true;
							}
						}
					}
					if(!$ignoreNotification) {
						$this->notifyRequestComplete($completeRequest);
					}
				}
			}
			while(!$this->isStopped && ($this->activeRequests || $this->queue->count()));
		}
		catch(Exception $exception) {
		}

		$this->isActive = false;

		if($curlHandle && is_resource($curlHandle)) {
			curl_multi_close($curlHandle);
		}

		if(!empty($exception)) {
			throw $exception;
		}

        /** @noinspection PhpUndefinedMethodInspection */
        $this->callbacks->onComplete($this);
	}
}
