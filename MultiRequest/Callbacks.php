<?php

/**
 * @see https://github.com/barbushin/multirequest
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 *
 */
class MultiRequest_Callbacks {

	protected $callbacks;

	public function add($name, $callback) {
		if(!is_callable($callback)) {
			if(is_array($callback)) {
				$callbackName = (is_object($callback[0]) ? get_class($callback[0]) : $callback[0]) . '::' . $callback[1];
			}
			else {
				$callbackName = $callback;
			}
			throw new Exception('Callback "' . $callbackName . '" with name "' . $name . '" is not callable');
		}
		$this->callbacks[$name][] = $callback;
	}

	public function call($name, $arguments) {
		if(isset($this->callbacks[$name])) {
			foreach($this->callbacks[$name] as $callback) {
				call_user_func_array($callback, $arguments);
			}
		}
	}

	public function __call($method, $arguments = array()) {
		$this->call($method, $arguments);
	}
}