<?php

if ($action == 'login')
{
	if (workerLoggedIn())
	{
		$controller->redirect('dashboard');
	}

	$validate = new Validate;
	$validate->add('login', $_POST['worker_login'], 'login require');
	$validate->add('password', $_POST['worker_password'], 'password require');

	if (!$validate->getValid())
	{
		setMessage('error::Nieprawidłowy format loginu lub hasła.');
		$controller->redirect('back');
	}

	$worker = new Worker($db);
	$result = $worker->login($validate->getValidData());

	if (!$result)
	{
		setMessage($worker->message);
		$controller->redirect('account/login-form');
	}

	if (isset($_SESSION['locationUrl']['afterLogin']))
	{
		$redirect = $_SESSION['locationUrl']['afterLogin'];
		unset($_SESSION['locationUrl']['afterLogin']);
		$controller->redirect($redirect);
	}
	
	$controller->redirect('dashboard');


}
elseif ($action == 'login-form')
{
	if (workerLoggedIn())
	{
		$controller->redirect('dashboard');
	}

	$view->addView('account-login-form');
	$view->renderSingle();

}
elseif ($action == 'logout')
{
	if (!workerLoggedIn())
	{
		$controller->redirect('account/login-form');
	}

	session_destroy();
	session_start();
	setMessage('success::Wylogowano.');
	$controller->redirect('account/login-form');
}
elseif ($action == 'reset-password')
{
	if (!workerLoggedIn())
	{
		setMessage('warn::Wymagane zalogowanie się.');
		$controller->redirect('account/login-form/redir');
	}

	$validate = new Validate;
	$account = new Account($db);
	$account->setWorkerId($controller->id());
	$result = $account->resetPassword();

	setMessage($account->message);
	$controller->redirect('back');

}
elseif ($action == 'proceed-password')
{
	if (workerLoggedIn())
	{
		$controller->redirect('dashboard');
	}

	$validate = new Validate;
	$validate->add('login', $controller->paramNumber(1), ('alnum require'));
	$validate->add('token', $controller->paramNumber(2), ('alnum require'));

	if (!$validate->getValid())
	{
		setMessage('warn::Link resetowania hasła jest niepoprawny.');
		$controller->redirect('account/login-form');
	}

	$account = new Account($db);
	$result = $account->proceedResetPassword($validate->getValidData());

	if (!$result)
	{
		setMessage($account->message);
		$controller->redirect('account/login-form');
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
		$controller->redirect('dashboard');
	}

	$validate = new Validate;
	$validate->add('password', $_POST['password'], 'password 8 20');
	$validate->add('workerId', $_POST['worker_id'], 'integer require');// index z inputa wtf
	$validate->add('token', $_POST['token'], 'alnum require');

	if ($validate->getValid())
	{
		if($validate->password != $_POST['password_repeat'])
		{
			setMessage('info::Oba hasła powiny być identyczne.');
			$controller->redirect('back');
		}

		$account = new Account($db);
		$result = $account->passwordChange($validate->getValidData());
	
		setMessage($account->message);
		
		$controller->redirect('account/login-form');
	}

	if ($validate->_fieldFail == 'password')
	{
		setMessage('info::Hasło powinno mieć długość między 8 a 20 znaków.');
	}

	// setMessage('info::Nie można zresetować hasła dla tego użytkownika (valid failed).');
	$controller->redirect('back');

}
elseif ($action == 'create')
{
	formBackup(); // wyleci

	$validate = new Validate;
	$validate->add('fullName', $_POST['name'], 'fullname require 6 40');
	$validate->add('email', $_POST['email'], 'email require 8 60');

	if($validate->getValid())
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
	$controller->redirect('back');


} elseif ($action == 'proceed-register')
{
	if (workerLoggedIn())
	{
		$controller->redirect('dashboard');
	}

	$validate = new Validate;
	$validate->add('name', $controller->paramNumber(1), 'alnum require');
	$validate->add('token',$controller->paramNumber(2), 'alnum require');

	if (!$validate->getValid())
	{
		setMessage('warn::Link do konfiguracji konta jest niepoprawny.');
		$controller->redirect('account/login-form');
	}

	$account = new Account($db);
	$result = $account->proceedRegister($validate->getValidData());

	if (!$result)
	{
		// $view->addData('message', $account->message); //użytć tego jak już ujednolice komunikaty
		setMessage($account->message);
		$controller->redirect('account/login-form');
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
		$controller->redirect('dashboard');
	}
	
	formBackup(); // wyleci

	$validate = new Validate;
	$validate->add('login', $_POST['login'], ('login require 4 30'));
	$validate->add('password', $_POST['password'], ('password require 8 20'));
	$validate->add('workerId', $_POST['worker_id'], ('integer require'));
	$validate->add('token', $_POST['token'], ('alnum require'));
	$passwordRepeat = trim($_POST['password_repeat']);

	if ($validate->getValid())
	{
		if ($validate->password != $passwordRepeat)
		{
			setMessage('warn::Oba hasła powiny być identyczne.');
			$controller->redirect('back');
		}

		$account = new Account($db);
		$result = $account->createPassword($validate->getValidData());
		setMessage($account->message);

		$controller->redirect('account/login-form');
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
	$controller->redirect('back');

}
elseif ($action == 'disable')
{
	$account = new Account($db);
	$account->setWorkerId($controller->id());
	$account->disable();

	setMessage($account->message);
	$controller->redirect('back');

}
elseif ($action == 'enable') // powtarzam podobny kod, ogarnąć
{
	$account = new Account($db);
	$account->setWorkerId($controller->id());
	$account->enable();

	setMessage($account->message);
	$controller->redirect('back');

}
elseif ($action == 'edit')
{
	echo '<br><br> TODO edit account worker';
	exit;
}

// atdev.ddns.net/sk/account/proceed-register/nazwiskoiimie/0d4bd044b12654e