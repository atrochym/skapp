<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class Account
{
	public string $message;
	public array $worker = [];
	private int $workerId;

	public function __construct(private Database $db)
	{}

	public function create(array $data)
	{
		$values = ['email' => $data['email']];
		$result = $this->db->run("SELECT id FROM workers WHERE email = :email", $values);

		if ($result->rowCount())
		{
			$this->message = 'warn::Adres "' . $data['email'] . '" jest przypisany do innego pracownika.';
			return false;
		}

		$tempPassword = substr(md5(rand() . 'tmp'), 0, 20);
		$tempLogin = str_replace([' ', 'ą', 'ć', 'ę', 'ł', 'ń', 'ó', 'ś', 'ż', 'ź'], ['', 'a', 'c', 'e', 'l', 'n', 'o', 's', 'z', 'z'], strtolower($data['fullName']));

		$values = [
			'login' => $tempLogin . '_temp',
			'name' => $data['fullName'],
			'email' => $data['email'],
			'password' => $tempPassword,
			'security_token' => $this->makeToken()
		];
		$newWorkerId = $this->db->insert('workers', $values);
		$token =  $this->makeToken(15);

		$values = [
			'worker_id' => $newWorkerId,
			'worker_login' => $tempLogin.'_temp',
			'token' => $token,
			'ip' => $_SERVER['REMOTE_ADDR']
		];

		$this->db->insert('password_changes', $values);

		$activationUrl = "http://atdev.ddns.net/sk/account/proceed-register/$tempLogin/$token";
		$subject = 'Studio-Komp - rejestracja użytkownika.';
		$body = 'Cześć '.$data['fullName'].', Twoje konto zostało utworzone. Kliknij w link i dokończ konfigurację konta. <br><br><a href="'.$activationUrl.'">'.$activationUrl.'</a> ';

		$result = $this->sendEmail($data['email'], $subject, $body);

		if (!$result)
		{
			$this->message = 'error::Błąd podczas wysyłania linka aktywacyjnego. Adres odbiorcy jest poprany? Możesz podesłać ten link: '.$activationUrl; // tymczasowe
			return false;
		}

		$this->message = 'success::Pracownik został zarejestrowany i otrzymał mail z linkiem aktywacyjnym.';
		return true;
	}

	public function proceedRegister(array $data)
	{
		$values = [
			'login' => $data['name'] . '_temp',
			'token' => $data['token'],
		];

		$worker = $this->db->run('SELECT id, worker_id FROM password_changes WHERE worker_login = :login AND token = :token AND is_valid = 1', $values)->fetch();

		if (!$worker)
		{
			$this->message = 'warn::Link do konfiguracji konta jest niepoprawny lub wygasł.';
			return false;
		}

		$this->worker['id'] = $worker['worker_id'];
		$this->worker['name'] = $data['name'];
		$this->worker['token'] =  $data['token'];
		
		return true;
	}

	public function proceedResetPassword(array $data)
	{
		$values = [
			'login' => $data['login'],
			'token' => $data['token'],
		];

		$exec = $this->db->run('SELECT id, worker_id FROM password_changes WHERE worker_login = :login AND token = :token AND is_valid = 1', $values)->fetch();

		if (!$exec)
		{
			$this->message = 'warn::Link resetowania hasła jest niepoprawny lub wygasł.';
			return false;
		}

		$worker = $this->db->run('SELECT name, login FROM workers WHERE id = :id', ['id' => $exec['worker_id']])->fetch();

		$this->worker['id'] = $exec['worker_id'];
		$this->worker['name'] = $worker['name'];
		$this->worker['login'] = $worker['login'];
		$this->worker['token'] = $data['token'];

		return true;
	}

	public function passwordChange(array $data)
	{
		$values = [
			'workerId' => $data['workerId'],
			'token' => $data['token'],
		];
		
		$request = $this->db->run('SELECT id FROM password_changes WHERE worker_id = :workerId AND token = :token AND is_valid = 1 LIMIT 1', $values)->fetch();

		if (!$request)
		{
			$this->message = 'error::Żądanie resetowania hasła nie zostało odnalezione.';
			return false;
		}

		$this->worker['id'] = $data['workerId']; // dla preparePassword, może jakoś inaczej?

		$values = [
			'password' => $this->preparePassword($data['password']),
			'workerId' => $data['workerId'],
		];
		$this->db->run('UPDATE workers SET password = SHA2(:password, 256) WHERE id = :workerId', $values);
		$this->db->run('UPDATE password_changes SET is_valid = 0 WHERE id = :id', ['id' => $request['id']]);
		$worker = $this->db->run('SELECT name FROM workers WHERE id = :id', ['id' => $data['workerId']])->fetch();

		$this->message = 'success::Hasło dla '. $worker['name'] .' zostało zmienione, zaloguj się.';
		return true;
	}

	public function createPassword(array $data)
	{
		$values = [
			'login' => $data['login'],
		];

		$exec = $this->db->run('SELECT id FROM workers WHERE login = :login', $values)->fetch();

		if ($exec)
		{
			$this->message = 'info::Ten login jest już zajęty.';
			return false;
		}

		$values = [
			'worker_id' => $data['workerId'],
			'token' => $data['token'],
		];

		$exec = $this->db->run('SELECT id FROM password_changes WHERE worker_id = :worker_id AND token = :token AND is_valid = 1 LIMIT 1', $values)->fetch();

		if (!$exec)
		{
			$this->message = 'info::Link do konfiguracji konta jest niepoprawny lub stracił ważność.';
			return false;
		}

		$this->worker['id'] = $data['workerId']; // dla preparePassword

		$values = [
			'password' => $this->preparePassword($data['password']),
			'id' => $data['workerId'],
			'login' => $data['login'],
		];

		$this->db->run('UPDATE workers SET password = SHA2(:password, 256), login = :login, is_activated = 1 WHERE id = :id', $values);
		$this->db->run('UPDATE password_changes SET is_valid = 0 WHERE id = :id', ['id' => $exec['id']]);
		$this->db->insert('permissions', ['worker_id' => $data['workerId']]);
		$this->worker = $this->db->run('SELECT name FROM workers WHERE id = :id', ['id' => $data['workerId']])->fetch();

		$this->message = 'success::Konfiguracja konta "'. $this->worker['name'] .'" została zakończona, zaloguj się.';
		return true;
	}

	public function resetPassword()
	{
		if (!$this->getData() || $this->isPasswordResetBegin())
		{
			return;
		}

		// przenieść do innej metody
		$token = $this->makeToken(15);
		$values = [
			'worker_id' => $this->workerId,
			'worker_login' => $this->worker['login'],
			'token' => $token,
			'ip' => $_SERVER['REMOTE_ADDR']
		];

		$this->db->insert('password_changes', $values);

		$url = 'http://atdev.ddns.net/sk/account/proceed-password/'. $this->worker['login'] .'/'. $token;
		$subject = 'Studio-Komp - resetowanie hasła.';
		$body = 'Cześć '. $this->worker['name'].', procedura resetowania hasła została rozpoczęta. Kliknij w link i ustaw nowe hasło. <br><br><a href="'.$url.'">'.$url.'</a> ';

		if (!$this->sendEmail($this->worker['email'], $subject, $body))
		{
			$this->message = 'error::Wystąpił błąd podczas wysyłania wiadomości email.';
			return false;
		}

		$this->message = 'success::Hasło dla użytkownika "'. $this->worker['name'] .'" zostało zresetowane. Otrzyma on maila z dalszymi instrukcjami.';
		return true;
	}

	public function passwordChangeRequest()
	{

	}

	public function isPasswordResetBegin()
	{
		if (!$this->getData())
		{
			return;
		}

		$values = ['workerId' => $this->workerId];
		$result = $this->db->run('SELECT id FROM password_changes WHERE worker_id = :workerId AND is_valid = 1', $values)->fetch();

		if ($result)
		{
			$this->message = 'info::Procedura resetowania hasła dla '. $this->worker['name'] .' jest już w toku, nie można wdrożyć jej ponownie. (w sumie nie wiem co dalej)';
			return $result;
		}

		return false;
	}

	public function disable()
	{
		if (!$this->getData())
		{
			return;
		}

		$values = [
			'token' => $this->makeToken(10),
			'workerId' => $this->workerId,
		];

		$this->db->run('UPDATE workers SET is_disabled = 1, security_token = :token WHERE id = :workerId', $values);
		$this->message = 'success::Konto pracownika '. $this->worker['name'] .' zostało wyłączone.';
		return true;
	}

	public function enable()
	{
		if (!$this->getData())
		{
			return;
		}

		$values = [
			'token' => $this->makeToken(10),
			'workerId' => $this->workerId,
		];

		$this->db->run('UPDATE workers SET is_disabled = 0, security_token = :token WHERE id = :workerId', $values);
		$this->message = 'success::Konto pracownika '. $this->worker['name'] .' zostało włączone.';
		return true;
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
			require './PHPMailer/src/Exception.php';
			require './PHPMailer/src/PHPMailer.php';
			require './PHPMailer/src/SMTP.php';
		
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

	private function preparePassword(string $password)
	{
		$salt = $this->worker['id'] . 'id-sk-app';
		return $salt . $password;
	}
	
	public function setWorkerId(int $workerId)
	{
		$this->workerId = $workerId;
	}

	private function getData()
	{
		$data = ['workerId' => $this->workerId];
		$result = $this->db->run('SELECT * FROM workers WHERE id = :workerId', $data);

		if (!$result->rowCount())
		{
			$this->message = 'error::Konto o podanym loginie nie istnieje.';
			return false;
		}


		if ((int) $this->workerId < 1)
		{
			$this->message = 'warn::Niepoprawny identyfikator pracownika.';
			return false;
		}

		if ($this->worker)
		{
			return $this->worker;
		}

		$values = ['workerId' => $this->workerId];
		$worker = $this->db->run('SELECT * FROM workers WHERE id = :workerId', $values)->fetch();

		if (!$worker)
		{
			$this->message = 'warn::Pracownik o podanym identyfikatorze nie istnieje.';
			return false;
		}

		$this->worker = $worker;
		return $this->worker;
	}
}

//klasa worker i account mają podobne metody, trza to posprzątać