<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class Account
{
	private string $fullName, $email;
	public string $message;
	public array $worker;

	public function __construct(private Database $db, private Validate $validate)
	{
		
	}

	public function create()
	{
		$this->validate->add('fullName', $this->fullName, 'fullname require 6 40');
		$this->validate->add('email', $this->email, 'email require 8 60');

		if(!$this->validate->getValid())
		{
			if($this->validate->_fieldFail == 'fullName')
			{
				$this->message = '{warn}Imię i Nazwisko może zawierać wyłącznie znaki a-ź, spację oraz minimum 6, maksymalnie 40 znaków.';
				return false;
			}

			if ($this->validate->_fieldFail == 'email')
			{
				$this->message = '{warn}E-mail ma nieprawidłowy format lub długość.';
				return false;
			}

			$this->message = '{error}Nie udało się stworzyć konta (validate failed).';
			return false;
		}

		require './PHPMailer-skapp/src/Exception.php';
		require './PHPMailer-skapp/src/PHPMailer.php';
		require './PHPMailer-skapp/src/SMTP.php';

		$data = ['email' => $this->validate->email];
		$result = $this->db->run("SELECT id FROM workers WHERE email = :email", $data);

		if ($result->rowCount())
		{
			$this->message = '{warn}Adres ' . $this->validate->email . ' już jest zajęty.';
			return false;
		}

		$tempPassword = substr(md5(rand() . 'tmp'), 0, 20);
		$tempLogin = str_replace([' ', 'ą', 'ć', 'ę', 'ł', 'ń', 'ó', 'ś', 'ż', 'ź'], ['', 'a', 'c', 'e', 'l', 'n', 'o', 's', 'z', 'z'], strtolower($this->validate->fullName));

		$data = [
			'login' => $tempLogin . '_temp',
			'name' => $this->validate->fullName,
			'email' => $this->validate->email,
			'password' => $tempPassword,
			'security_token' => $this->makeToken()
		];
		$newWorkerId = $this->db->insert('workers', $data);
		$token =  $this->makeToken(15);

		$data = [
			'worker_id' => $newWorkerId,
			'worker_login' => $tempLogin.'_temp',
			'token' => $token,
			'ip' => $_SERVER['REMOTE_ADDR']
		];

		$this->db->insert('password_changes', $data);

		$activationUrl = "http://atdev.ddns.net/sk/account/proceed-register/$tempLogin/$token";
		$subject = 'Studio-Komp - rejestracja użytkownika.';
		$body = 'Cześć '.$this->validate->fullName.', Twoje konto zostało utworzone. Kliknij w link i dokończ konfigurację konta. <br><br><a href="'.$activationUrl.'">'.$activationUrl.'</a> ';

		$result = $this->sendEmail($this->validate->email, $subject, $body);

		if (!$result)
		{
			$this->message = '{error}Błąd podczas wysyłania linka aktywacyjnego. Adres odbiorcy jest poprany? Możesz podesłać ten link: '.$activationUrl;
			return false;
		}

		$this->message = '{success}Pracownik został zarejestrowany i otrzymał mail z linkiem aktywacyjnym.';
		return true;
	}

	public function proceedRegister(array $data)
	{
		$this->validate->add('name', $data['urlName'], 'alnum require');
		$this->validate->add('token', $data['urlToken'], 'alnum require');

		if (!$this->validate->getValid())
		{
			$this->message = '{warn}Link do konfiguracji konta jest niepoprawny.';
			return false;
		}

		$data = [
			'login' => $this->validate->name . '_temp',
			'token' => $this->validate->token,
		];

		$worker = $this->db->run('SELECT id, worker_id FROM password_changes WHERE worker_login = :login AND token = :token AND is_valid = 1', $data)->fetch();

		if (!$worker)
		{
			$this->message = '{warn}Link do konfiguracji konta jest niepoprawny lub wygasł.';
			return false;
		}

		$this->worker['id'] = $worker['worker_id'];
		$this->worker['name'] = $this->validate->name;
		$this->worker['token'] =  $this->validate->token;
		
		return true;
	}

	public function proceedResetPassword(array $data)
	{
		$this->validate->add('login', $data['urlLogin'], ('alnum require'));
		$this->validate->add('token', $data['urlToken'], ('alnum require'));

		if (!$this->validate->getValid())
		{
			$this->message = '{warn}Link resetowania hasła jest niepoprawny.';
			return false;
		}

		$data = [
			'login' => $this->validate->login,
			'token' => $this->validate->token,
		];

		$exec = $this->db->run('SELECT id, worker_id FROM password_changes WHERE worker_login = :login AND token = :token AND is_valid = 1', $data)->fetch();

		if (!$exec)
		{
			$this->message = '{warn}Link resetowania hasła jest niepoprawny lub wygasł.';
			return false;
		}

		$worker = $this->db->run('SELECT name, login FROM workers WHERE id = :id', ['id' => $exec['worker_id']])->fetch();

		$this->worker['id'] = $exec['worker_id'];
		$this->worker['name'] = $worker['name'];
		$this->worker['login'] = $worker['login'];
		$this->worker['token'] = $this->validate->token;

		return true;
	}

	public function passwordChange(array $data)
	{
		$this->validate->add('password', $data['password'], 'password 8 20');
		$this->validate->add('workerId', $data['worker_id'], 'integer require');
		$this->validate->add('token', $data['token'], 'alnum require');

		if (!$this->validate->getValid())
		{
			if ($this->validate->_fieldFail == 'password')
			{
				$this->message = '{warn}Hasło powinno mieć długość między 8 a 20 znaków.';
				return false;
			}

			$this->message = '{warn}Nie można zresetować hasła dla tego użytkownika (valid failed).';
			return false;
		}

		if($this->validate->password !=  $data['password_repeat'])
		{
			$this->message = '{warn}Oba hasła powiny być identyczne.';
			return false;
		}

		$data = [
			'workerId' => $this->validate->workerId,
			'token' => $this->validate->token,
		];
		
		$request = $this->db->run('SELECT id FROM password_changes WHERE worker_id = :workerId AND token = :token AND is_valid = 1 LIMIT 1', $data)->fetch();

		$this->worker['id'] = $this->validate->workerId; // dla preparePassword

		$data = [
			'password' => $this->preparePassword($this->validate->password),
			'workerId' => $this->validate->workerId,
		];
		$this->db->run('UPDATE workers SET password = SHA2(:password, 256) WHERE id = :workerId', $data);
		$this->db->run('UPDATE password_changes SET is_valid = 0 WHERE id = :id', ['id' => $request['id']]);
		$worker = $this->db->run('SELECT name FROM workers WHERE id = :id', ['id' => $this->validate->workerId])->fetch();

		$this->message = 'Hasło dla '. $worker['name'] .' zostało zmienione, zaloguj się.';
		return true;
	}

	public function createPassword(array $data)
	{
		$this->validate->add('login', $data['login'], ('login require 4 30'));
		$this->validate->add('password', $data['password'], ('password require 8 20'));
		$this->validate->add('workerId', $data['worker_id'], ('integer require'));
		$this->validate->add('token', $data['token'], ('alnum require'));
		$passwordRepeat = trim($data['password_repeat']);

		if (!$this->validate->getValid())
		{
			if ($this->validate->_fieldFail == 'login')
			{
				$this->message = '{warn}Login ma nieprawidłowy format lub długość. Poprawna długość to 4-30 znaków.';
				return false;
			}

			if ($this->validate->_fieldFail == 'password')
			{
				$this->message = '{warn}Hasło powinno mieć długość między 8 a 20 znaków.';
				return false;
			}

			$this->message = 'Dane wejściowe są niepoprawne (valid failed).';
			return false;
		}

		if($this->validate->password != $passwordRepeat)
		{
			$this->message = 'Oba hasła powiny być identyczne.';
			return false;
		}

		$data = [
			'login' => $this->validate->login,
		];

		$exec = $this->db->run('SELECT id FROM workers WHERE login = :login', $data);

		if ($exec->rowCount())
		{
			$this->message = 'Ten login jest już zajęty.';
			return false;
		}

		$data = [
			'worker_id' => $this->validate->workerId,
			'token' => $this->validate->token,
		];

		$exec = $this->db->run('SELECT id FROM password_changes WHERE worker_id = :worker_id AND token = :token AND is_valid = 1 LIMIT 1', $data)->fetch();

		if (!$exec)
		{
			$this->message = 'Link do konfiguracji konta jest niepoprawny lub stracił ważność.';
			return false;
		}

		$this->worker['id'] = $this->validate->workerId; // dla preparePassword

		$data = [
			'password' => $this->preparePassword($this->validate->password),
			'id' => $this->validate->workerId,
			'login' => $this->validate->login,
		];

		$this->db->run('UPDATE workers SET password = SHA2(:password, 256), login = :login, is_activated = 1 WHERE id = :id', $data);
		$this->db->run('UPDATE password_changes SET is_valid = 0 WHERE id = :id', ['id' => $exec['id']]);
		$this->db->insert('permissions', ['worker_id' => $this->validate->workerId]);
		$this->worker = $this->db->run('SELECT name FROM workers WHERE id = :id', ['id' => $this->validate->workerId])->fetch();

		$this->message = 'Konfiguracja konta '. $this->worker['name'] .' została zakończona, zaloguj się.';
		return true;
	}

	public function resetPassword(int $workerId)
	{
		$worker = $this->db->run('SELECT * FROM workers WHERE id = :id LIMIT 1', ['id' => $workerId])->fetch();

		if (!$worker)
		{
			$this->message = 'Zresetowanie hasła nie było możliwe, pracownik nie istnieje.';
			return false;
		}

		// wywalić do innej metody
		$token = $this->makeToken(15);
		$data = [
			'worker_id' => $workerId,
			'worker_login' => $worker['login'],
			'token' => $token,
			'ip' => $_SERVER['REMOTE_ADDR']
		];

		$this->db->insert('password_changes', $data);

		$url = "http://atdev.ddns.net/sk/account/proceed-password/$worker[login]/$token";
		$subject = 'Studio-Komp - resetowanie hasła.';
		$body = 'Cześć '. $worker['name'].', procedura resetowania hasła została rozpoczęta. Kliknij w link i ustaw nowe hasło. <br><br><a href="'.$url.'">'.$url.'</a> ';

		if (!$this->sendEmail($worker['email'], $subject, $body))
		{
			$this->message = 'Wystąpił błąd podczas wysyłania wiadomości email.';
			return false;
		}

		$this->message = 'Hasło dla użytkownika '. $worker['name'] .' zostało zresetowane. Otrzyma on maila z dalszymi instrukcjami.';
		return true;
	}

	public function passwordChangeRequest()
	{
		
	}

	public function logout()
	{
		session_destroy();
		session_start();

		$this->message = 'Zostałeś wylogowany';
		return true;
	}
	
	private function sendEmail(string $addres, string $subject, string $body)
	{
		try
		{
			require './PHPMailer-skapp/src/Exception.php';
			require './PHPMailer-skapp/src/PHPMailer.php';
			require './PHPMailer-skapp/src/SMTP.php';
		
			$mail = new PHPMailer(false); // true for log
			// $mail->SMTPDebug = SMTP::DEBUG_SERVER;
			$mail->IsSMTP();
			$mail->CharSet='UTF-8';
			$mail->Host = 'smtp.gmail.com';
			$mail->Port = 465 ;
			$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
			$mail->SMTPAuth = true;
			$mail->IsHTML(true);
			$mail->Username = MAILER_LOGIN;
			$mail->Password = MAILER_PASS;
			$mail->setFrom(MAILER_FROM_EMAIL, MAILER_FROM_NAME);
			$mail->AddAddress($addres);
			$mail->Subject = $subject;
			$mail->Body = $body;

			$mail->Send();

			return true;
		}
		catch (Exception $e)
		{
			logToFile($mail->ErrorInfo, 'phpmailer.log');
			return false;
		}
	}
	

	private function makeToken($lenght = 10)
	{
		return substr(md5(rand() . 'token'), 0, $lenght);
	}

	public function setFullName(string $fullName)
	{
		$this->fullName = $fullName;
	}

	public function setEmail(string $email)
	{
		$this->email = $email;
	}

	public function setLogin(string $login)
	{
		$this->login = $login;
	}

	public function setPassword(string $password)
	{
		$this->password = $password;
	}

	private function preparePassword(string $password)
	{
		$salt = $this->worker['id'] . 'id-sk-app';
		return $salt . $password;
	}
	
	private function getAccountData()
	{
		$data = ['login' => $this->login];
		$result = $this->db->run("SELECT * FROM workers WHERE login = :login", $data);

		if (!$result->rowCount())
		{
			$this->message = '{error}Konto o podanym loginie nie istnieje.';
			return false;
		}
	}
}

//klasa worker i account mają podobne metody, trza to posprzątać