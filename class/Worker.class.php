<?php

class Worker
{
	public $message;
	private $workerId;
	private $worker;

	public function __construct(private Database $db)
	{}

	public function deleteDevice(int $deviceId)
	{
		$workerDevice = $this->db->run('SELECT worker_id, fingerprint FROM workers_devices WHERE id = :id', $deviceId)->fetch();
		
		if (!$workerDevice)
		{
			$this->message = 'error::Urządzenie nie zostało odnalezione.';
			return false;
		}

		elseif ($workerDevice['worker_id'] != $this->workerId)
		{
			$this->message = 'error::Nie masz uprawnień do tej akcji.';
			return false;	
		}

		$this->db->run('UPDATE workers_devices SET deleted = 1 WHERE id = :id', $deviceId);
		if ($workerDevice['fingerprint'] == getFromCookie('fingerprint'))
		{
			$token = substr(sha1(rand() . 'token'), 0, 15); // jest zduplikowana do metody w class worker, w index.php
			$values = [
				'token' => $token,
				'workerId' => $this->workerId,
			];
			$this->db->run('UPDATE workers SET security_token = :token WHERE id = :workerId', $values);
		}
		return true;
	}

	public function trustedDevice(): int|false
	{
		$fingerprint = getFromCookie('fingerprint');
		if (!$fingerprint || !checkDeviceFingerprint($fingerprint))
		{
			return false;
		}

		$values = [
			'workerId' => $this->workerId,
			'fingerprint' => $fingerprint,
		];
		$trustedDevice = $this->db->run('SELECT * FROM workers_devices WHERE worker_id = :workerId AND fingerprint = :fingerprint AND deleted = 0 AND status = "allow" LIMIT 1', $values)->fetch();

		if (!$trustedDevice)
		{
			return false;
		}
			
		$this->db->run('UPDATE workers_devices SET last_login = NOW() WHERE id = :id', $trustedDevice['id']);
		setcookie('fingerprint', $fingerprint, time() + 2592000, '/', '', true, true);
		return $trustedDevice['id'];

	}

	public function login(array $data)
	{
		$worker = $this->db->run('SELECT * FROM workers WHERE login = :login LIMIT 1', $data['login'])->fetch();

		if (!$worker)
		{
			$this->message = 'error::Konto o podanym loginie nie istnieje.';
			return false;
		}

		$this->worker = $worker;
		$this->workerId = $worker['id'];

		if (!password_verify($data['password'], $worker['password']))
		{
			$this->message = 'error::Podane hasło jest nieprawidłowe.';
		}
		elseif ($this->worker['disabled'])
		{
			$this->message = 'warn::Konto zostało wyłączone przez administratora.';
		}
		elseif (!$this->worker['activated'])
		{
			$this->message = 'warn::Konto nie zostało aktywowane, sprawdź swoją pocztę email.';
		}
		else
		{
			session_regenerate_id(true);
			$this->setSessionData();
			$maskWorkerId = maskWorkerId();
			$auth = makeAuth();
			setcookie('workerId', $maskWorkerId, time() + 604800, '/', '', true, true); //7d
			setcookie('auth', $auth, time() + 604800, '/', '', true, true); //7d
			return true;
		}
		return false;
	}

	public function autologin()
	{
		$worker = $this->db->run('SELECT * FROM workers WHERE id = :id LIMIT 1', $this->workerId)->fetch();

		if (!$worker)
		{
			return false;
		}

		$this->worker = $worker;

		if ($this->worker['disabled'])
		{
			$this->message = 'warn::Konto zostało wyłączone przez administratora.';
		}
		elseif (!$this->worker['activated'])
		{
			$this->message = 'warn::Konto nie zostało aktywowane, sprawdź swoją pocztę email.';
		}
		else
		{
			session_regenerate_id(true);
			$this->setSessionData();
			return true;
		}
		return false;
	}

	public function setSessionData()
	{
		setToSession('workerId', $this->worker['id']);
		setToSession('workerName', $this->worker['name']);
		setToSession('workerLogin', $this->worker['login']);
		setToSession('workerSecurityToken', $this->worker['security_token']);
		setToSession('trustedDevice', $this->trustedDevice());
		setToSession('permission', $this->getPermissions());

		$this->getWorkersList();
	}

	private function getPermissions()
	{
		if (!$this->getData())
		{
			return false;
		}

		$permissions = $this->db->run('SELECT * FROM permissions WHERE worker_id = :workerId', $this->workerId)->fetch();
		array_shift($permissions);

		return $permissions;
	}

	public function getWorkersList()
	{
		if (!isset($_SESSION['workersList']))
		{
			$_SESSION['workersList'] = $this->db->run('SELECT id, name FROM workers')->fetchAll(PDO::FETCH_KEY_PAIR);
		}
	
		return $_SESSION['workersList'];
	}

	public function getData()
	{
		if ((int) $this->workerId < 1)
		{
			$this->message = 'warn::Niepoprawny identyfikator pracownika.';
			return false;
		}

		if ($this->worker)
		{
			return $this->worker;
		}

		$worker = $this->db->run('SELECT * FROM workers WHERE id = :workerId', $this->workerId)->fetch();

		if (!$worker)
		{
			$this->message = 'warn::Pracownik o podanym identyfikatorze nie istnieje.';
			return false;
		}

		$this->worker = $worker;
		return $this->worker;
	}

	public function setWorkerId(int $workerId)
	{
		$this->workerId = $workerId;
	}
}

//klasa worker i account mają podobne metody, trza to posprzątać