<?php

class Router {

	/*
	 *   PRIVATE
	 */

	// Array containing all routes and their callbacks
	private $routes = [];

	/*
	 *   PUBLIC
	 */

	// Add Route with GET Method
	public function get($url, $callbackClass, $callbackMethod) {
		if (is_callable([$callbackClass, $callbackMethod]) && substr($url, 0, 1) == '/') {
			$this->routes[] = [
				'url'      => $url,
				'callback' => [$callbackClass, $callbackMethod],
				'method'   => 'GET'
			];
		}
	}

	// Add Route with POST Method
	public function post($url, $callbackClass, $callbackMethod) {
		if (is_callable([$callbackClass, $callbackMethod]) && substr($url, 0, 1) == '/') {
			$this->routes[] = [
				'url'      => $url,
				'callback' => [$callbackClass, $callbackMethod],
				'method'   => 'POST'
			];
		}
	}

	// Add Route with GET Method
	public function delete($url, $callbackClass, $callbackMethod) {
		if (is_callable([$callbackClass, $callbackMethod]) && substr($url, 0, 1) == '/') {
			$this->routes[] = [
				'url'      => $url,
				'callback' => [$callbackClass, $callbackMethod],
				'method'   => 'DELETE'
			];
		}
	}

	// Start Routing
	public function run() {
		header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS, PUT, PATCH');

		$url = strtok($_SERVER['REQUEST_URI'], '?');
		$method = $_SERVER['REQUEST_METHOD'];
		$parameters = null;

		if ($method === 'POST') {
			$parameters = $_POST;
		} elseif ($method === 'GET' || $method === 'DELETE') {
			$parameters = $_GET;
		}

		foreach ($this->routes as $route) {
			if ($route['method'] === $_SERVER['REQUEST_METHOD'] && $route['url'] === $url) {
				$route['callback']($parameters);
				return;
			}
		}

		// If route was not found
		//header('HTTP/1.0 404 Not Found');
		echo "Error 404 - Not found";
	}
}