<?php

if ($action == 'login')
{
	if (workerLoggedIn())
	{
		$router->redirect('/dashboard');
	}

	$validate = new Validate;
	$validate->add('login', $_POST['worker_login'], 'login require');
	$validate->add('password', $_POST['worker_password'], 'password require');

	if (!$validate->check())
	{
		setMessage('error::Nieprawidłowy format loginu lub hasła.');
		$router->redirect('back');
	}

	$worker = new Worker($db);
	$result = $worker->login($validate->getValidData());

	if (!$result)
	{
		// if ($worker->message == 'notTrusted')
		// {
		// 	$router->redirect('/account/register-device');
		// }

		setMessage($worker->message);
		$router->redirect('/account/login-form');
	}

	// $ip = $_SERVER['REMOTE_ADDR'];
	// $workerDevice = parseUserAgent($_SERVER['HTTP_USER_AGENT']);
	// $workerDevice = $ip . $workerDevice['platform'] .' '. $workerDevice['browser'];

	// $fingerprint = getFromCookie('fingerprint');

	// if (!$fingerprint || !fingerprint($workerDevice, $fingerprint) || !$worker->device($fingerprint))
	// {
	// 	$router->redirect('/account/register-device');
	// }

	// if (isset($_SESSION['locationUrl']['afterLogin']))
	// {
	// 	$redirect = $_SESSION['locationUrl']['afterLogin'];
	// 	unset($_SESSION['locationUrl']['afterLogin']);
	// 	$router->redirect($redirect);
	// }
	
	if (isset($_SESSION['locationUrl']['previous']))
	{
		$redirect = $_SESSION['locationUrl']['previous'];
		// unset($_SESSION['locationUrl']['afterLogin']);
		$router->redirect($redirect);
	}
	
	$router->redirect(HOME_PAGE);


}
elseif ($action == 'login-form')
{
	if (workerLoggedIn())
	{
		$router->redirect(HOME_PAGE);
	}

	$view->addView('account-login-form');
	$view->renderSingle();

}
elseif ($action == 'logout')
{
	if (!workerLoggedIn())
	{
		$router->redirect('/account/login-form');
	}

	setcookie('workerId', '', 0, '/');
	setcookie('auth', '', 0, '/');
	session_destroy();
	session_start();
	// session_regenerate_id(true);
	setMessage('success::Wylogowano.');
	$router->redirect('/account/login-form');
}
elseif ($action == 'reset-password')
{
	if (!workerLoggedIn())
	{
		setMessage('warn::Wymagane zalogowanie się.');
		$router->redirect('/account/login-form/redir');
	}

	$validate = new Validate;
	$account = new Account($db);
	$account->setWorkerId($router->getId());
	$result = $account->resetPassword();

	setMessage($account->message);
	$router->redirect('back');

}
elseif ($action == 'proceed-password')
{
	if (workerLoggedIn())
	{
		$router->redirect('/dashboard');
	}

	$validate = new Validate;
	$validate->add('login', $router->getParam(1), ('alnum require'));
	$validate->add('token', $router->getParam(2), ('alnum require'));

	if (!$validate->check())
	{
		setMessage('warn::Link resetowania hasła jest niepoprawny.');
		$router->redirect('/account/login-form');
	}

	$account = new Account($db);
	$result = $account->proceedResetPassword($validate->getValidData());

	if (!$result)
	{
		setMessage($account->message);
		$router->redirect('/account/login-form');
	}

	$data = [
		'XworkerId' => $account->worker['id'],
		'XworkerName' => $account->worker['name'],
		'XworkerLogin' => $account->worker['login'],
		'token' =>  $account->worker['token']
	];

	$view->addView('account-reset-password-form');
	$view->addData($data);
	$view->renderSingle();

}
elseif ($action == 'password-change')
{
	if (workerLoggedIn())
	{
		$router->redirect('/dashboard');
	}

	$validate = new Validate;
	$validate->add('password', $_POST['password'], 'password 8 20');
	$validate->add('workerId', $_POST['worker_id'], 'integer require');// index z inputa wtf
	$validate->add('token', $_POST['token'], 'alnum require');

	if ($validate->check())
	{
		if($validate->password != $_POST['password_repeat'])
		{
			setMessage('info::Oba hasła powiny być identyczne.');
			$router->redirect('back');
		}

		$account = new Account($db);
		$result = $account->passwordChange($validate->getValidData());
	
		setMessage($account->message);
		
		$router->redirect('/account/login-form');
	}

	if ($validate->_fieldFail == 'password')
	{
		setMessage('info::Hasło powinno mieć długość między 8 a 20 znaków.');
	}

	// setMessage('info::Nie można zresetować hasła dla tego użytkownika (valid failed).');
	$router->redirect('back');

}
elseif ($action == 'create')
{
	formBackup(); // wyleci

	$validate = new Validate;
	$validate->add('fullName', $_POST['name'], 'fullname require 6 40');
	$validate->add('email', $_POST['email'], 'email require 8 60');

	if($validate->check())
	{
		$account = new Account($db, $validate);
		$account->create($validate->getValidData());
	
		setMessage($account->message);
	}

	if($validate->_fieldFail == 'fullName')
	{
		setMessage('warn::Imię i Nazwisko może zawierać wyłącznie znaki a-ź, spację oraz minimum 6, maksymalnie 40 znaków.');
	}

	if ($validate->_fieldFail == 'email')
	{
		setMessage('warn::E-mail ma nieprawidłowy format lub długość.');
	}

	// setMessage('warn::Nie udało się stworzyć konta (validate failed).');
	$router->redirect('back');


} elseif ($action == 'proceed-register')
{
	if (workerLoggedIn())
	{
		$router->redirect('/dashboard');
	}

	$validate = new Validate;
	$validate->add('name', $router->getParam(1), 'alnum require');
	$validate->add('token',$router->getParam(2), 'alnum require');

	if (!$validate->check())
	{
		setMessage('warn::Link do konfiguracji konta jest niepoprawny.');
		$router->redirect('/account/login-form');
	}

	$account = new Account($db);
	$result = $account->proceedRegister($validate->getValidData());

	if (!$result)
	{
		// $view->addData('message', $account->message); //użytć tego jak już ujednolice komunikaty
		setMessage($account->message);
		$router->redirect('/account/login-form');
	}

	$data = [
		'XworkerId' => $account->worker['id'],
		'XworkerName' => $account->worker['name'],
		'token' => $account->worker['token'],
	];

	$view->addView('account-create-password-form');
	$view->addData($data);
	$view->renderSingle();

}
elseif ($action == 'create-password')
{
	if (workerLoggedIn())
	{
		$router->redirect('/dashboard');
	}
	
	formBackup(); // wyleci

	$validate = new Validate;
	$validate->add('login', $_POST['login'], ('login require 4 30'));
	$validate->add('password', $_POST['password'], ('password require 8 20'));
	$validate->add('workerId', $_POST['worker_id'], ('integer require'));
	$validate->add('token', $_POST['token'], ('alnum require'));
	$passwordRepeat = trim($_POST['password_repeat']);

	if ($validate->check())
	{
		if ($validate->password != $passwordRepeat)
		{
			setMessage('warn::Oba hasła powiny być identyczne.');
			$router->redirect('back');
		}

		$account = new Account($db);
		$result = $account->createPassword($validate->getValidData());
		setMessage($account->message);

		$router->redirect('/account/login-form');
	}

	if ($validate->_fieldFail == 'login')
	{
		setMessage('warn::Login ma nieprawidłowy format lub długość. Poprawna długość to 4-30 znaków.');
	}

	if ($validate->_fieldFail == 'password')
	{
		setMessage('warn::Hasło powinno mieć długość między 8 a 20 znaków.');
	}

	// setMessage('warn::Dane wejściowe są niepoprawne (valid failed).');
	$router->redirect('back');

}
elseif ($action == 'disable')
{
	$account = new Account($db);
	$account->setWorkerId($router->getId());
	$account->disable();

	setMessage($account->message);
	$router->redirect('back');

}
elseif ($action == 'enable') // powtarzam podobny kod, ogarnąć
{
	$account = new Account($db);
	$account->setWorkerId($router->getId());
	$account->enable();

	setMessage($account->message);
	$router->redirect('back');

}
elseif ($action == 'edit')
{
	echo '<br><br> TODO edit account worker';
	exit;
}
elseif ($action == 'register-device' && workerLoggedIn() && !getFromSession('trustedDevice'))
{
	$fingerprint = getFromCookie('fingerprint');

	if ($fingerprint && checkDeviceFingerprint($fingerprint))
	{
		$values = [
			'workerId' => getFromSession('workerId'),
			'fingerprint' => $fingerprint,
		];
		$trustedDevice = $db->run('SELECT * FROM workers_devices WHERE worker_id = :workerId AND fingerprint = :fingerprint AND deleted = 0 LIMIT 1', $values)->fetch();

		if ($trustedDevice && $trustedDevice['status'] == NULL)
		{
			$view->addView('account-register-device-accept');
			$view->renderSingle();
		}
		elseif ($trustedDevice && $trustedDevice['status'] == 'allow')
		{
			$db->run('UPDATE workers_devices SET last_login = NOW() WHERE id = :id', $trustedDevice['id']);
			setToSession('trustedDevice', $trustedDevice['id']);
			$router->redirect();
		}
		elseif ($trustedDevice && $trustedDevice['status'] == 'deny')
		{
			session_destroy();
			session_start();
			setMessage('error::Urządzenie nie zostało zaakceptowane przez administratora.');
			$router->redirect('/account/login-form');
		}
	}

	if (workerPermit('session_manager'))
	{
		if (!empty($_POST))
		{
			$validate = new Validate;
			$validate->add('deviceName', $_POST['device_name'], 'require text 3 60');
			if (!$validate->check())
			{
				setMessage('warn::Nazwa urządzenia ma nieprawidłową długość lub format.');
				$router->redirect('back');
			}

			$newDevice = registerDevice($db, $validate->deviceName);
			if ($newDevice)
			{
				setcookie('fingerprint', $newDevice['fingerprint'], time() + 2592000, '/', '', true, true);
				setToSession('trustedDevice', $newDevice['id']);
				setMessage('info::Urządzenie '. $validate->deviceName .' zostało dodane do listy zaufanych. Możesz zarządzać zaufanymi urządzeniami w ustawieniach.');
				$router->redirect();
			}

			setMessage('warn::Nie udało się zarejestrować urządzenia.');
			$router->redirect('back');
		}


			$device = parseUserAgent();
			$device = $device['platform'] .' '. $device['browser'];

			$view->addData(['device' => $device]);
			$view->addView('account-register-device');
			$view->renderSingle();
	}
	else
	{
		$newDevice = registerDevice($db);
		if ($newDevice)
		{
			setcookie('fingerprint', $newDevice['fingerprint'], time() + 2592000, '/', '', true, true);
			$view->addView('account-register-device-accept');
			$view->renderSingle();
		}
		setMessage('warn::Nie udało się zarejestrować urządzenia.');
		$router->redirect('back');
	}
}
elseif($action == 'device-accept' && testworkerPermit('session_manager'))
{
	// może możliwość cofnięcia przez ileś tam minut
	// wywalić komunikat z showMessage bo nadpisuje inny
	$id = $router->getId();
	
	$db = new Database;
	$validate = new Validate;
	$validate->add('id', $id, 'require integer');

	if (!$validate->check())
	{
		setMessage('warn::Błąd walidacji danych.');
		$router->redirect('back');
	}

	$device = $db->run(
		'SELECT wd.*, w.name AS workerName FROM workers_devices AS wd 
		JOIN workers AS w ON wd.worker_id = w.id
		WHERE wd.id = :id LIMIT 1', $validate->id)
		->fetch();

	if (!$device || $device['deleted'] || $device['accepted'])
	{
		setMessage('error::Request nie istnieje.');
		$router->redirect('back');
	}

	$db->run('UPDATE workers_devices SET status = "allow" WHERE id = :id', $validate->id);
	setMessage("success::Urządzenie użytkownika '".$device['workerName']."' zostało zaakceptowane.");
	$router->redirect('back');
}
elseif($action == 'device-decline' && testworkerPermit('session_manager'))
{
	// może możliwość cofnięcia przez ileś tam minut
	// wywalić komunikat z showMessage bo nadpisuje inny
	$id = $router->getId();
	
	$db = new Database;
	$validate = new Validate;
	$validate->add('id', $id, 'require integer');

	if (!$validate->check())
	{
		setMessage('warn::Błąd walidacji danych.');
		$router->redirect('back');
	}

	$device = $db->run(
		'SELECT wd.*, w.name AS workerName FROM workers_devices AS wd 
		JOIN workers AS w ON wd.worker_id = w.id
		WHERE wd.id = :id LIMIT 1', $validate->id)
		->fetch();

	if (!$device || $device['deleted'] || $device['accepted'])
	{
		setMessage('error::Request nie istnieje.');
		$router->redirect('back');
	}

	$db->run('UPDATE workers_devices SET status = "deny" WHERE id = :id', $validate->id);
	setMessage("warn::Urządzenie użytkownika '".$device['workerName']."' zostało odrzucone.");
	$router->redirect('back');
}
elseif ($action == 'device-delete' && testworkerPermit('session_manager'))
{
	$id = $router->getId();
	$workerId = getFromSession('workerId');
	$validate = new Validate;
	$validate->add('id', $id, 'require integer');

	if (!$validate->check())
	{
		setMessage('error::Błąd walidacji danych.');
		$router->redirect('back');
	}

	$worker = new Worker($db);
	$worker->setWorkerId($workerId);
	if ($worker->deleteDevice($validate->id))
	{
		setMessage("success::Urządzenie zostało usunięte z listy dozwolonych.");
		$router->redirect('back');
	}
	else
	{
		setMessage($worker->message);
		$router->redirect('back');
	}

	// $workerDevice = $db->run('SELECT worker_id, fingerprint FROM workers_devices WHERE id = :id', $validate->id)->fetch();

	// if (!$workerDevice)
	// {
	// 	setMessage('error::Urządzenie nie istnieje.');
	// 	$router->redirect('back');

	// }
	// elseif ($workerDevice['worker_id'] != $workerId)
	// {
	// 	setMessage('warn::Brak uprawnień.');
	// 	$router->redirect('back');

	// }

	// $db->run('UPDATE workers_devices SET deleted = 1 WHERE id = :id', $validate->id);
	// setMessage('success::Urządzenie zostało usunięte.');
	// $router->redirect('back');
}





// 	if(empty($_POST))
// 	{
// 		if (workerPermit('session_manager'))
// 		{
// 			// jest też zduplikowamy w worker.class
// 			$device = parseUserAgent();
// 			$device = $device['platform'] .' '. $device['browser'];

// 			$view->addData(['device' => $device]);
// 			$view->addView('account-register-device');
// 			$view->renderSingle();
// 		}
// 		else
// 		{

// 			// wyślij rquest o zaakceptowanie urządzenia
// 			$view->addView('account-register-device-accept');
// 			$view->renderSingle();
// 		}
// 	}

// 	$validate = new Validate;
// 	$validate->add('deviceName', $_POST['device_name'], 'require text 3 60');
// 	if (!$validate->check())
// 	{
// 		setMessage('warn::Nazwa urządzenia ma nieprawidłową długość lub format.');
// 		$router->redirect('back');
// 	}

// 	$workerId = getFromSession('workerId');
// 	$accepted = workerPermit('session_manager') ? 1 : 0;
// 	$device = parseUserAgent();
// 	$fingerprint = getFromCookie('fingerprint');

// 	if (!$fingerprint || !deviceFingerprint($fingerprint))
// 	{
// 		$fingerprint = deviceFingerprint();
// 	}

// 	$values = [
// 		'worker_id' => $workerId,
// 		'name' => $validate->deviceName,
// 		'type' => $device['platform'] . ' ' .$device['browser'],
// 		'fingerprint' => $fingerprint,
// 		'ip' => ip2long(getIP()),
// 		'accepted' => $accepted,
// 	];
// 	$result = $db->insert('workers_devices', $values);
// 	setToSession('trustedDevice', 1);
// 	setcookie('fingerprint', $fingerprint, time() + 2592000, '/', '', true, true);

// 	setMessage('info::Urządzenie '. $validate->deviceName .' zostało dodane do listy zaufanych. Możesz zarządzać zaufanymi urządzeniami w ustawieniach.');
// 	$router->redirect();

// }
else
{
	$router->redirect();
}
// atdev.ddns.net/sk/account/proceed-register/nazwiskoiimie/0d4bd044b12654e