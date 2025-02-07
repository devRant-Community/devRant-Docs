<?php

include_once('config.php');

class DB {

	/*
	 *   PRIVATE
	 */

	private $db;
	private $queries = [];

	// Convert Multidimensional to Single-Dimensional Array
	private function flattenArray($arr, $prefix = "") {
		if (!is_array($arr)) {
			return false;
		}
		$result = array();
		foreach ($arr as $key => $value) {
			if ($prefix == "") {
				$newPrefix = $key;
			} else {
				$newPrefix = $prefix . '.' . $key;
			}

			if (is_array($value)) {
				$result = array_merge($result, $this->flattenArray($value, $newPrefix));
			} else {
				$result[$newPrefix] = $value;
			}
		}
		return $result;
	}

	/*
	 *   PUBLIC
	 */

	// Constructor
	public function __construct() {
		$this->db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

		if ($this->db->connect_error) {
			die('Connect Error (' . $this->db->connect_errno . ') ' . $this->db->connect_error);
		}
	}

	// Destructor
	public function __destruct() {
		$this->db->close();
	}

	// Read JSON file containing all queries
	public function getQueries($jsonFile) {
		$rawQueries = json_decode(file_get_contents($jsonFile), true);

		$this->queries = $this->flattenArray($rawQueries);
	}

	// Perform Query with parameters
	public function query($name, $parameters = []) {
		$query = $this->queries[$name];

		preg_match_all('/\{([a-zA-z]+)\}/', $query, $matches);

		foreach ($matches[1] as $match) {
			if(!isset($parameters[$match])) {
				continue;
			}

			$parameter = $this->db->real_escape_string($parameters[$match]);

			$query = str_replace('{' . $match . '}', $parameter, $query);
		}

		return $this->rawQuery($query);
	}

	// Perform a raw query
	public function rawQuery($query) {
		return $this->db->query($query);
	}

	// Return errors
	public function error() {
		return $this->db->error;
	}

	// Return last insert ID
	public function insert_id() {
		return $this->db->insert_id;
	}
}