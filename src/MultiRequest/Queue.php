<?php

/**
 * @see https://github.com/barbushin/multirequest
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 *
 */
class MultiRequest_Queue {
	
	protected $requests = array();

	public function push(MultiRequest_Request $request) {
		$this->requests[] = $request;
	}

	public function pop() {
		return array_shift($this->requests);
	}

	public function count() {
		return count($this->requests);
	}

	public function clear() {
		$this->requests = array();
	}
}
