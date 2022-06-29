<?php

class Worker
{
	public $message;
	private $worker;

	public function __construct(private Database $db)
	{}

	public function login(array $data)
	{
		$values = ['login' => $data['login']];
		$result = $this->db->run('SELECT * FROM workers WHERE login = :login', $values)->fetch();

		if (!$result)
		{
			$this->message = '{error}Konto o podanym loginie nie istnieje.';
			return false;
		}

		$this->worker = $result;

		$password = $this->preparePassword($data['password']);

		$values = ['password' => $password];
		$password = $this->db->run("SELECT SHA2(:password, 256)", $values)->fetchColumn();

		if ($this->worker['password'] !== $password)
		{
			$this->message = 'error::Podane hasło jest nieprawidłowe.';
			return false;
		}

		if ($this->worker['is_disabled'])
		{
			$this->message = 'warn::Konto zostało wyłączone przez administratora.';
			return false;
		}

		if (!$this->worker['is_activated'])
		{
			$this->message = 'warn::Konto nie zostało aktywowane, sprawdź swoją pocztę email.';
			return false;
		}

		$values = ['workerId' => $this->worker['id']];
		$permissions = $this->db->run('SELECT * FROM permissions WHERE worker_id = :workerId', $values)->fetch();

		array_shift($permissions);
		foreach ($permissions as $permission => $value)
		{
			$_SESSION['permission'][$permission] = $value;
			//setToSession nie wspiera tablic...
		}
		
		setToSession('workerId', $this->worker['id']);
		setToSession('workerName', $this->worker['name']);
		setToSession('workerLogin', $this->worker['login']);
		setToSession('workerSecurityToken', $this->worker['security_token']);

		return true;
	}

	private function reloadPrivileges()
	{
		
	}

	private function preparePassword(string $password)
	{
		$salt = $this->worker['id'] . 'id-sk-app';
		return $salt . $password;
	}
}

//klasa worker i account mają podobne metody, trza to posprzątać