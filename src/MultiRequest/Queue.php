<?php
namespace MultiRequest;



/**
 * @see https://github.com/barbushin/multirequest
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 *
 */
class Queue {
	
	protected $requests = array();

	public function push(Request $request) {
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
