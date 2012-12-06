<?php

/**
 * @see https://github.com/barbushin/multirequest
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 *
 */
class MultiRequest_Defaults {

	protected $properties = array();
	protected $methods = array();

	public function applyToRequest(MultiRequest_Request $request) {
		foreach($this->properties as $property => $value) {
			$request->$property = $value;
		}
		foreach($this->methods as $method => $calls) {
			foreach($calls as $arguments) {
				call_user_func_array(array($request, $method), $arguments);
			}
		}
	}

	public function __set($property, $value) {
		$this->properties[$property] = $value;
	}

	public function __call($method, $arguments = array()) {
		$this->methods[$method][] = $arguments;
	}
}
