<?php

class Model {
	public $message;
	public $messageTest; // test
	private $data = [];
	
	public function __construct(public Database $db) {

		// $this->set(['workerName' => $_SESSION['workerName']]);
		$this->data['workerName'] = $_SESSION['workerName'];
		$this->data['workerId'] = $_SESSION['workerId'];

		$this->message = new Message;
		$this->messageTest = new MessageTest; // test
		$this->reloadPrivileges();
		// $this->showMessage();
		// v($this->data);
	}

	// public function db() {
	// 	return $this->db;
	// }
	
	// używać??

	public function getRedirect() {
		
		if (!$_SESSION['redirect']['previousUrl']) {
			throw new Exception('ERR: get url redir');
		}

		// $_SESSION['redirect']['doit'] = false;
		return $_SESSION['redirect']['previousUrl'];
	}


	// coś wymyślić z tymi logami
	public function setLog($receive_id, $log) {
		
		$workerId = $_SESSION['workerId'];
		$log = $this->db->exec("INSERT INTO receives_log (id, receive_id, worker_id, content) VALUES (NULL, $receive_id, $workerId, '$log')");
		if (!$log) {
			throw new Exception('receive_log:failed');
		}
	}

	public function getData() {
		return $this->data;
	}
	
	public function get($data) {
		return $this->data[$data];
	}

	public function mess() {
		return $this->message;
	}

	// ogarnąć czy lista pracowników z sesji jest aktualna
	function getWorkersList() {

		if (isset($_SESSION['workersList'])) {
			return $_SESSION['workersList'];
		}

		$workers = $this->db->query('SELECT id, name FROM workers');
		$workers = $workers->fetchAll(PDO::FETCH_KEY_PAIR);
		$_SESSION['workersList'] = $workers;
		return $_SESSION['workersList'];
	}


	// public function setMessage($name, $type) {

	// 	$_SESSION['message']['name'] = $name;
	// 	$_SESSION['message']['type'] = $type;

	// 	v($this->data);
	// 	echo('SETTING MESSAGE');
	// }

	// public function showMessage() {
	// 	if (isset($_SESSION['message'])) {
	// 		$message = $_SESSION['message']['name'];
	// 		$type = $_SESSION['message']['type'];
	// 		unset($_SESSION['message']);

	// 		$this->data['messageContent'] = $message;
	// 		$this->data['messageType'] =  $type;
	// 	}
	// }

	public function reloadPrivileges() {
		// niektóre części kodu są powielone ww AccountModel
		if (!$_SESSION['workerId']) {
			return;
		}

		$workerId = $_SESSION['workerId'];

		$worker = $this->db->query("SELECT security_token FROM workers WHERE id = $workerId LIMIT 1");
		$worker = $worker->fetch();

		if ($worker['security_token'] != $_SESSION['workerSecurityToken']) {
			$account = $this->db->query("SELECT is_disabled FROM workers WHERE id = $workerId LIMIT 1");
			$account = $account->fetch();

			if ($account['is_disabled']) {
				session_destroy();
				session_start();

				// nie mam lepszego pomysłu
				$this->message->set(['messageContent' => 'Twoje konto zostało dezaktywowane przez administratora.', 
									'messageType' => '']);
				redirect('account/login-form');
			}

			$permissions = $this->db->query("SELECT * FROM permissions WHERE worker_id = $workerId");
			$permissions = $permissions->fetch();
			array_shift($permissions);
			foreach ($permissions as $permission => $value) {
			// 	echo 'nazwa: ' . $key . ' klucz: '. $value .'<BR>';
				$_SESSION['permission'][$permission] = $value;
			}

			e('<br> Perm reloaded <br>');

			$newToken = substr(md5(rand() . 'token'), 0, 10);
			$updateToken = $this->db->query("UPDATE workers SET security_token = '$newToken' WHERE id = $workerId");

			if (!$updateToken) {
				session_destroy();
				session_start();
				throw new Exception('ERR: update token failed.');
			}

			$_SESSION['workerSecurityToken'] = $newToken;
		}

	}

	public function workerPermit($permission) {
		
		if (!isset($_SESSION['permission'][$permission])) {
			exit('ERR permission missing '. $permission);
		}

		return (bool) $_SESSION['permission'][$permission];
	}
}

?>