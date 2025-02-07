<?php

include_once('core/DB.php');

class Auth {

	/*
	 *   PRIVATE
	 */

	private $db;

	// Check if the username exists
	private function userExists($username) {
		$result = $this->db->query('auth.getUser', [
			'username' => $username
		]);

		if ($result) {
			return ($result->num_rows > 0);
		} else {
			die(json_encode([
				'success'    => false,
				'error'      => 'Could not check if user exists',
				'mysqlError' => $this->db->error()
			]));
		}
	}

	// Get user by his ID
	private function getUserByID($id) {
		$result = $this->db->query('auth.getUserByID', [
			'id' => $id
		]);

		if ($result) {
			if ($result->num_rows > 0) {
				$user = $result->fetch_assoc();

				return $user;
			}

			return false;
		} else {
			die(json_encode([
				'success'    => false,
				'error'      => 'Could not check if user exists',
				'mysqlError' => $this->db->error()
			]));
		}
	}

	// Check password
	private function checkUser($username, $password) {
		$result = $this->db->query('auth.getUser', [
			'username' => $username
		]);

		if ($result && $result->num_rows > 0) {
			$user = $result->fetch_assoc();

			return password_verify($password, $user['password']);
		} else {
			return false;
		}
	}

	// Get userID
	private function getUserID($username) {
		$result = $this->db->query('auth.getUser', [
			'username' => $username
		]);

		if ($result && $result->num_rows > 0) {
			$user = $result->fetch_assoc();
			return $user['id'];
		} else {
			return false;
		}
	}

	private function generateRandomToken() {
		return md5(uniqid(rand(), true));
	}

	private function generateUniqueID() {
		// No clue how, but this returns a number of about 10 digits lol
		return abs(crc32(uniqid(rand())));
	}


	/*
	 *   PUBLIC
	 */

	// Constructor
	public function __construct() {
		$this->db = new DB();
		$this->db->getQueries('queries.json');

		// All output is in JSON
		header("Content-Type: json, application/json");

		// Some headers that are needed...
		header('Access-Control-Allow-Origin: *');
	}

	/*
		Login

		Params:
		username - Login Username
		password - Login Password
	*/
	public function login($params) {
		// Check 'username' parameter
		if (!isset($params['username']) || empty($params['username'])) {
			die(json_encode(['success' => false, 'error' => 'Username is required']));
		}

		// Check 'password' parameter
		if (!isset($params['password']) || empty($params['password'])) {
			die(json_encode(['success' => false, 'error' => 'Password is required']));
		}

		if ($this->userExists($params['username'])) {
			if ($this->checkUser($params['username'], $params['password'])) {
				// Create Token

				$tokenData = [
					'id'     => $this->generateUniqueID(),
					'key'    => $this->generateRandomToken(),
					'userid' => $this->getUserID($params['username']),
					'username' => $params['username']
				];

				$result = $this->db->query('auth.create.token', $tokenData);

				if ($result) {
					echo json_encode(['success' => true, 'token' => $tokenData]);
				} else {
					echo json_encode([
						'success'    => false,
						'error'      => 'Error while creating token. Try again later',
						'mysqlError' => $this->db->error()
					]);
				}
			} else {
				echo json_encode(['success' => false, 'error' => 'Username is already taken or password is wrong']);
			}
		} else {
			if (isset($params['confirmSignUp']) && !empty($params['confirmSignUp']) && $params['confirmSignUp'] === 'true') {
				// If the user has confirmed that he wants to register...

				// Create User
				$result = $this->db->query('auth.create.user', [
					'username'     => $params['username'],
					'passwordHash' => password_hash($params['password'], PASSWORD_DEFAULT)
				]);

				if ($result) {
					// Create Token

					$tokenData = [
						'id'       => $this->generateUniqueID(),
						'key'      => $this->generateRandomToken(16),
						'userid'   => $this->getUserID($params['username']),
						'username' => $params['username']
					];

					$result = $this->db->query('auth.create.token', $tokenData);

					if ($result) {
						// Return Token
						echo json_encode(['success' => true, 'token' => $tokenData]);
					} else {
						echo json_encode([
							'success'    => false,
							'error'      => 'Error while creating token. Try again later',
							'mysqlError' => $this->db->error()
						]);
					}
				} else {
					echo json_encode([
						'success'    => false,
						'error'      => 'Could not create user',
						'mysqlError' => $this->db->error()
					]);
				}
			} else {
				// Let the user confirm that he wants to register...
				echo json_encode(['success' => false, 'confirmNeeded' => true, 'params' => $params]);
			}
		}
	}

	/*
		Check if a token is valid
		Set $callFromQA if you call this method from the Q&A Class (to check tokens) - prevents outputting JSON Errors

		Params:
		token_id - Token ID
		token_key - Token Key

		Returns the user ID on success
	*/
	public function checkToken($params, $callFromQA = false) {
		// Check parameters (using foreach for less lines)
		foreach (['token_id', 'token_key'] as $requiredParam) {
			if (!isset($params[$requiredParam]) || empty($params[$requiredParam])) {
				if ($callFromQA) {
					return false;
				} else {
					die(json_encode(['success' => false, 'error' => "Parameter '$requiredParam' is required"]));
				}
			}
		}

		$result = $this->db->query('auth.getToken', [
			'id' => $params['token_id']
		]);

		if ($result) {
			if ($result->num_rows == 0) {
				if ($callFromQA) {
					return false;
				} else {
					die(json_encode(['success' => false, 'error' => 'Token not found']));
				}
			}

			$token = $result->fetch_assoc();

			if ($token['token_key'] === $params['token_key']) {
				$user = $this->getUserByID($token['userid']);

				if ($user) {
					$token['isAdmin'] = $user['is_admin'];
					if ($callFromQA) {
						return $token;
					} else {
						$token['username'] = $user['username'];
						echo json_encode(['success' => true, 'token' => $token]);
					}
				} else {
					if ($callFromQA) {
						return false;
					} else {
						die(json_encode(['success' => false, 'error' => 'User does not exist anymore.']));
					}
				}
			} else {
				if ($callFromQA) {
					return false;
				} else {
					die(json_encode(['success' => false, 'error' => 'Token Key is wrong']));
				}
			}
		} else {
			if ($callFromQA) {
				return false;
			} else {
				die(json_encode([
					'success'    => false,
					'error'      => 'Error while getting token. Try again later',
					'mysqlError' => $this->db->error()
				]));
			}
		}
	}
}