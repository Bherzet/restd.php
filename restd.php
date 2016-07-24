<?php

/**
 * restd.php - a minimalist library to ease up implementing simple REST API in PHP.
 * Copyright (c) 2016 Tomas Zima
 */

interface Handler {
	public function handle($http_method, $path, $args, $data);
}

class Router {
	public function __construct($base_path) {
		$this->base_path = $base_path;
		$this->paths = array();
		$this->default_handler = new NotFoundErrorHandler();
	}

	public function addHandler($http_method, $path, $handler) {
		if (!isset($this->paths[$http_method])) {
			$this->paths[$http_method] = array();
		}

		if (is_array($path)) {
			for ($i = 0; $i < count($path); $i++) {
				$this->addHandler($http_method, $path[$i], $handler);
			}
		} else {
			$this->paths[$http_method][$path] = $handler;
		}
	}

	public function setDefaultHandler($handler) {
		$this->default_handler = $handler;
	}

	public function process() {
		$http_method = $_SERVER['REQUEST_METHOD'];
		$path = $this->getRequestPath();
		$p_path = $this->getPathParts($path);

		$executed_handlers = array();

		foreach (array_keys($this->paths[$http_method]) as $mask) {
			$p_mask = $this->getPathParts($mask);

			if (count($p_path) == count($p_mask)) {
				$args = array();

				for ($i = 0; $i < count($p_mask); $i++) {
					if ($this->isParameter($p_mask[$i])) {
						$args[substr($p_mask[$i], 1, -1)] = $p_path[$i];
					} else if ($p_mask[$i] != $p_path[$i]) {
						continue 2;
					}
				}

				$handler = $this->paths[$http_method][$mask];

				if (!in_array($handler, $executed_handlers, true)) {
					$executed_handlers[] = $handler;
					$handler->handle($http_method, $path, $args, file_get_contents("php://input"));
				}
			}
		}

		if (count($executed_handlers) == 0 && $this->default_handler) {
			$this->default_handler->handle($http_method, $path, null, file_get_contents("php://input"));
		}
	}

	private static function isParameter($p) {
		return strlen($p) > 0 ? $p[0] == '{' && $p[strlen($p) - 1] == '}' : false;
	}

	private function getRequestPath() {
		$path = substr(strtok($_SERVER['REQUEST_URI'], '?'), strlen($this->base_path));

		if ($path[0] !== '/') {
			$path = '/'.$path;
		}

		return $path[strlen($path) - 1] == '/' && strlen($path) != 1 ?
			substr($path, 0, strlen($path) - 1) : $path;
	}

	private static function getPathParts($path) {
		return explode('/', substr($path, $path[0] == '/'));
	}
}

abstract class JSONHandler implements Handler {
	public function __construct() {
		$this->response_code = 200;
		$this->response = null;
	}

	protected function setResponseCode($response_code) {
		$this->response_code = $response_code;
	}

	protected function setResponse($response) {
		$this->response = json_encode($response);
	}

	protected function setRawResponse($content) {
		$this->content = $content;
	}

	protected function setResult($code, $response) {
		$this->setResponseCode($code);
		$this->setResponse($response);
	}

	public function handle($http_method, $path, $args, $data) {
		try {
			$this->handleInternal($http_method, $path, $args, json_decode($data, true));
		} catch (Exception $e) {
			$this->response_code = 500;
		} finally {
			header("Content-Type: application/json");
			http_response_code($this->response_code);

			if ($this->response != null) {
				echo $this->response;
			}
		}
	}

	protected abstract function handleInternal($http_method, $path, $args, $data);
}

class NotFoundErrorHandler extends JSONHandler {
	protected function handleInternal($http_method, $path, $args, $data) {
		$this->setResult(400, array("error" => "No handler matches path ".$path." for HTTP method ".$http_method."."));
	}
}

