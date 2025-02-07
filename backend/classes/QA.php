<?php

include_once('core/DB.php');

class QA {

	/*
	 *   PRIVATE
	 */

	private $db;

	private $censors;

	// Get a user by ID
	private function getUser($id) {
		$result = $this->db->query('qa.getUser', [
			'id' => $id
		]);

		if ($result && $result->num_rows > 0) {
			$user = $result->fetch_assoc();
			return $user;
		}

		return false;
	}

	// Get all answers of a question
	private function getAnswers($questionid) {
		$result = $this->db->query('qa.getAnswers', [
			'questionid' => $questionid
		]);

		if ($result && $result->num_rows > 0) {
			$answers = [];
			while ($row = $result->fetch_assoc()) {
				$answers[] = $row;
			}

			foreach ($answers as $index => $question) {
				$user = $this->getUser($question['userid']);

				if (!$user) {
					unset($answers[$index]);
					continue;
				}

				$answers[$index]['username'] = $user['username'];
				unset($answers[$index]['questionid']);
			}

			return $answers;
		}

		return [];
	}

	// Get number of answers for a question
	private function getAnswerCount($questionid) {
		$result = $this->db->query('qa.getAnswerCount', [
			'questionid' => $questionid
		]);

		if ($result && $result->num_rows > 0) {
			$row = $result->fetch_assoc();

			$answerCount = $row['COUNT(*)'];

			if (is_numeric($answerCount)) {
				return $answerCount;
			}

			return 0;
		}

		return 0;
	}

	// Censor a string
	private function censor($str) {
		$newStr = $str;

		foreach ($this->censors as $censor) {
			$newStr = preg_replace("/($censor)/i", str_repeat('*', strlen($censor)), $newStr);
		}

		return $newStr;
	}

	private function getOwner($id, $type = 'question') {
		$result = null;

		if ($type == 'question') {
			$result = $this->db->query('qa.getQuestion', [
				'id' => $id
			]);
		} elseif ($type == 'answer') {
			$result = $this->db->query('qa.getAnswer', [
				'id' => $id
			]);
		}

		if ($result && $result->num_rows > 0) {
			$fetch = $result->fetch_assoc();

			return $fetch['userid'];
		}

		return 0;
	}


	/*
	 *   PUBLIC
	 */

	// Constructor
	public function __construct() {
		$this->db = new DB();
		$this->db->getQueries('queries.json');

		$this->auth = new Auth();

		$this->censors = json_decode(file_get_contents('censors.json'), true);

		// All output is in JSON
		header("Content-Type: json, application/json");

		// Some headers that are needed...
		header('Access-Control-Allow-Origin: *');
	}

	/*
		Get all questions

		Params:
		search - Filter with search
	*/
	public function allQuestions($params) {
		$result = null;
		if (isset($params['search']) && !empty($params['search'])) {
			$result = $this->db->query('qa.getAllQuestionsBySearch', [
				'search' => $params['search']
			]);
		} else {
			$result = $this->db->query('qa.getAllQuestions');
		}

		if ($result) {
			if ($result->num_rows == 0) {
				die(json_encode(['success' => true, 'questions' => []]));
			}

			$questions = [];
			while ($row = $result->fetch_assoc()) {
				if (isset($params['category']) && !empty($params['category']) && $params['category'] != 'all') {
					if ($row['category'] == $params['category']) {
						$questions[] = $row;
					}
				} else {
					$questions[] = $row;
				}
			}

			foreach ($questions as $index => $question) {
				$user = $this->getUser($question['userid']);

				if (!$user) {
					unset($questions[$index]);
					continue;
				}

				$questions[$index]['username'] = $user['username'];

				$answerCount = $this->getAnswerCount($question['id']);
				$questions[$index]['answerCount'] = $answerCount;
			}

			$result->free();

			$output = [];
			$output['success'] = true;
			$output['questions'] = $questions;

			echo json_encode($output);
		} else {
			die(json_encode(['success' => false]));
		}
	}

	/*
		Get a single question by id

		Params:
		id - The question ID
	*/
	public function question($params) {
		if (!isset($params['id']) || empty($params['id'])) {
			die(json_encode(['success' => false, 'error' => 'Parameter \'id\' is required']));
		}

		$id = $params['id'];

		$result = $this->db->query('qa.getQuestion', [
			'id' => $id
		]);

		if ($result) {
			if ($result->num_rows == 0) {
				die(json_encode(['success' => false, 'error' => 'Question not found']));
			}

			$question = $result->fetch_assoc();

			$user = $this->getUser($question['userid']);

			if (!$user) {
				die(json_encode(['success' => false, 'error' => 'User not found']));
			}

			$question['username'] = $user['username'];
			$question['answers'] = $this->getAnswers($question['id']);

			echo json_encode(['success' => true, 'question' => $question]);
		} else {
			die(json_encode(['success' => false]));
		}
	}

	/*
		Create a new question

		Params:
		userid - User ID of the user that created the question
		title - Question Title
		body - Question Body
		category - Question Category (must be an existing one -> https://devrant-docs.github.io/content/qa/categories.json)
	*/
	public function createQuestion($params) {
		// Check parameters (using foreach for less lines)
		foreach (['title', 'body', 'category', 'token_id', 'token_key'] as $requiredParam) {
			if (!isset($params[$requiredParam]) || empty($params[$requiredParam])) {
				die(json_encode(['success' => false, 'error' => ucfirst($requiredParam) . " is required"]));
			}
		}

		$token = $this->auth->checkToken($params, true);

		if ($token) {
			$result = $this->db->query('qa.create.question', [
				'userid'   => $token['userid'],
				'title'    => $this->censor($params['title']),
				'body'     => $this->censor($params['body']),
				'category' => $params['category']
			]);

			if ($result) {
				echo json_encode(['success' => true, 'id' => $this->db->insert_id()]);
			} else {
				die(json_encode([
					'success'    => false,
					'error'      => 'Error while creating Question. Try again later',
					'mysqlError' => $this->db->error()
				]));
			}
		} else {
			die(json_encode(['success' => false, 'error' => 'Invalid token']));
		}
	}

	/*
		Create a new answer

		Params:
		questionid - ID of the question where the answer should appear
		userid - User ID of the user that created the answer
		body - Answer Body
	*/
	public function createAnswer($params) {
		// Check parameters (using foreach for less lines)
		foreach (['questionid', 'body', 'token_id', 'token_key'] as $requiredParam) {
			if (!isset($params[$requiredParam]) || empty($params[$requiredParam])) {
				die(json_encode(['success' => false, 'error' => ucfirst($requiredParam) . " is required"]));
			}
		}

		$token = $this->auth->checkToken($params, true);

		if ($token) {
			$result = $this->db->query('qa.create.answer', [
				'questionid' => $params['questionid'],
				'userid'     => $token['userid'],
				'body'       => $this->censor($params['body'])
			]);

			if ($result) {
				echo json_encode(['success' => true]);
			} else {
				die(json_encode([
					'success'    => false,
					'error'      => 'Error while creating Answer. Try again later',
					'mysqlError' => $this->db->error()
				]));
			}
		} else {
			die(json_encode(['success' => false, 'error' => 'Invalid token']));
		}
	}

	/*
		Delete a question

		Params:
		questionid - Question ID
	*/
	public function deleteQuestion($params) {
		// Check parameters (using foreach for less lines)
		foreach (['questionid', 'token_id', 'token_key'] as $requiredParam) {
			if (!isset($params[$requiredParam]) || empty($params[$requiredParam])) {
				die(json_encode(['success' => false, 'error' => ucfirst($requiredParam) . " is required"]));
			}
		}

		$token = $this->auth->checkToken($params, true);

		if ($token) {
			$ownerID = $this->getOwner($params['questionid']);

			if (!$token['isAdmin'] && $ownerID != $token['userid']) {
				die(json_encode(['success' => false, 'error' => 'No permission']));
			}

			$result = $this->db->query('qa.delete.question', [
				'id' => $params['questionid']
			]);

			if ($result) {
				$result = $this->db->query('qa.delete.answersOfQuestion', [
					'questionid' => $params['questionid']
				]);

				if ($result) {
					echo json_encode(['success' => true]);
				} else {
					die(json_encode([
						'success'    => false,
						'error'      => 'Error while deleting all Answers of the Question. Try again later',
						'mysqlError' => $this->db->error()
					]));
				}
			} else {
				die(json_encode([
					'success'    => false,
					'error'      => 'Error while deleting Question. Try again later',
					'mysqlError' => $this->db->error()
				]));
			}
		} else {
			die(json_encode(['success' => false, 'error' => 'Invalid token']));
		}
	}

	/*
		Delete an Answer

		Params:
		answerid - Answer ID
	*/
	public function deleteAnswer($params) {
		// Check parameters (using foreach for less lines)
		foreach (['answerid', 'token_id', 'token_key'] as $requiredParam) {
			if (!isset($params[$requiredParam]) || empty($params[$requiredParam])) {
				die(json_encode(['success' => false, 'error' => ucfirst($requiredParam) . " is required"]));
			}
		}

		$token = $this->auth->checkToken($params, true);

		if ($token) {
			$ownerID = $this->getOwner($params['answerid'], 'answer');

			if (!$token['isAdmin'] && $ownerID != $token['userid']) {
				die(json_encode(['success' => false, 'error' => 'No permission']));
			}

			$result = $this->db->query('qa.delete.answer', [
				'id' => $params['answerid']
			]);

			if ($result) {
				echo json_encode(['success' => true]);
			} else {
				die(json_encode([
					'success'    => false,
					'error'      => 'Error while deleting Answer. Try again later',
					'mysqlError' => $this->db->error()
				]));
			}
		} else {
			die(json_encode(['success' => false, 'error' => 'Invalid token']));
		}
	}
}