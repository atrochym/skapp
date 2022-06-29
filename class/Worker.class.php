<?php

class Worker
{
	private $login, $password;
	private $message;
	private $worker;

	public function __construct(

		private object $db,
		private Validate $validate
		)
	{}

	public function login()
	{
		$this->validate->add('login', $this->login, 'login require');
		$this->validate->add('password', $this->password, 'password require');

		if (!$this->validate->getValid())
		{
			$this->message = '{error}Nieprawidłowy format loginu lub hasła.';
			return false;
		}

		$data = ['login' => $this->validate->login];
		$result = $this->db->run("SELECT * FROM workers WHERE login = :login", $data);

		if (!$result->rowCount())
		{
			$this->message = '{error}Konto o podanym loginie nie istnieje.';
			return false;
		}

		$this->worker = $result->fetch();

		$password = $this->preparePassword($this->validate->password);

		$data = ['password' => $password];
		$password = $this->db->run("SELECT SHA2(:password, 256)", $data)->fetchColumn();

		if ($this->worker['password'] !== $password)
		{
			$this->message = '{error}Podane hasło jest nieprawidłowe.';
			return false;
		}

		if ($this->worker['is_disabled'])
		{
			$this->message = '{error}Konto zostało wyłączone przez administratora.';
			return false;
		}

		if (!$this->worker['is_activated'])
		{
			$this->message = '{error}Konto nie zostało aktywowane, sprawdź swoją pocztę email.';
			return false;
		}
		
		setToSession('workerId', $this->worker['id']);
		setToSession('workerName', $this->worker['name']);
		setToSession('workerLogin', $this->worker['login']);

		return true;
	}

	public function setLogin(string $login)
	{
		$this->login = $login;
	}

	public function setPassword(string $password)
	{
		$this->password = $password;
	}

	public function getMessage()
	{
		return $this->message;
	}

	private function preparePassword(string $password)
	{
		$salt = $this->worker['id'] . 'id-sk-app';
		return $salt . $password;
	}
}

//klasa worker i account mają podobne metody, trza to posprzątać