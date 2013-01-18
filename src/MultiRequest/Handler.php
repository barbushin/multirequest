<?php

/**
 * @see https://github.com/barbushin/multirequest
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 *
 */
class MultiRequest_Handler {

	/**
	 * @var MultiRequest_RequestsDefaults
	 */
	protected $requestsDefaults;

	/**
	 * @var MultiRequest_Callbacks
	 */
	protected $callbacks;

	/**
	 * @var MultiRequest_Queue
	 */
	protected $queue;

	protected $connectionsLimit = 60;
	protected $totalTytesTransfered;
	protected $isActive;
	protected $isStarted;
	protected $isStopped;
	protected $activeRequests = array();
	protected $requestingDelay = 0.01;

	public function __construct() {
		if(!extension_loaded('curl')) {
			throw new Exception('CURL extension require to be installed and enabled in PHP');
		}
		$this->queue = new MultiRequest_Queue();
		$this->requestsDefaults = new MultiRequest_Defaults();
		$this->callbacks = new MultiRequest_Callbacks();
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

	protected function notifyRequestComplete(MultiRequest_Request $request) {
		$request->notifyIsComplete($this);
		$this->callbacks->onRequestComplete($request, $this);
	}

	/**
	 * @return MultiRequest_Request
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

	public function pushRequestToQueue(MultiRequest_Request $request) {
		$this->queue->push($request);
	}

	protected function sendRequestToMultiCurl($mcurlHandle, MultiRequest_Request $request) {
		$this->requestsDefaults->applyToRequest($request);
		curl_multi_add_handle($mcurlHandle, $request->getCurlHandle(true));
	}

	public function start() {
		if($this->isActive || $this->isStopped) {
			return;
		}
		$this->isActive = true;
		$this->isStarted = true;

		try {

			$this->mcurlHandle = $mcurlHandle = curl_multi_init();

			do {

				// send requests from queue to CURL
				if(count($this->activeRequests) < $this->connectionsLimit) {
					for($i = $this->connectionsLimit - count($this->activeRequests); $i > 0; $i--) {
						$request = $this->queue->pop();
						if($request) {
							$this->sendRequestToMultiCurl($mcurlHandle, $request);
							$this->activeRequests[$request->getId()] = $request;
						}
						else {
							break;
						}
					}
				}

				while(CURLM_CALL_MULTI_PERFORM === curl_multi_exec($mcurlHandle, $activeThreads)) {
					;
				}

				// check complete requests
				curl_multi_select($mcurlHandle, $this->requestingDelay);
				while($completeCurlInfo = curl_multi_info_read($mcurlHandle)) {
					$completeRequestId = MultiRequest_Request::getRequestIdByCurlHandle($completeCurlInfo['handle']);
					$completeRequest = $this->activeRequests[$completeRequestId];
					unset($this->activeRequests[$completeRequestId]);
					curl_multi_remove_handle($mcurlHandle, $completeRequest->getCurlHandle());
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
								// figure out whether we're dealign with an absolute or relative redirect (thanks to kmontag https://github.com/kmontag for this bugfix)
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

		if($mcurlHandle && is_resource($mcurlHandle)) {
			curl_multi_close($mcurlHandle);
		}

		if(!empty($exception)) {
			throw $exception;
		}

		$this->callbacks->onComplete($this);
	}
}
