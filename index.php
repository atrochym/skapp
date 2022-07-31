<?php

require 'config.php';
require 'functions.php';

// spl_autoload_register('classLoader');
session_start();

// ve($_SERVER);

$router = new Router;
$db = new Database;
// $view = new View;


// $module = $router->getModule();
// $action = $router->getAction();
// $id = $router->getId();

// if (!isset($_SESSION['workerId']) && $module !== 'module/account.php')
// {
// 	// setMessage('warn::Wymaganie zalogowanie się.');
	
// 	$_SESSION['locationUrl']['afterLogin'] = $_SESSION['locationUrl']['this']; // zapakować w funkcję czy coś

// 	$router->redirect('/account/login-form/redir');
// }

if(workerLoggedIn())
{
	$workerId = getFromSession('workerId');
	$worker = $db->run('UPDATE workers SET last_active = NOW() WHERE id = :workerId LIMIT 1', $workerId);
	$worker = $db->run('SELECT security_token FROM workers WHERE id = :workerId LIMIT 1', $workerId)->fetch();

	if ($worker['security_token'] != getFromSession('workerSecurityToken'))
	{
		$trustedDevice = getFromSession('trustedDevice');
		$trustedDevice = $db->run('SELECT deleted FROM workers_devices WHERE id = :id LIMIT 1', $trustedDevice)->fetch();

		if ($trustedDevice['deleted'])
		{
			session_destroy();
			session_start();
			session_regenerate_id(true);

			// nie mam lepszego pomysłu
			setMessage('info::Zostałeś wylogowany.');
			$router->redirect('/account/login-form');
		}

		$account = $db->run('SELECT disabled FROM workers WHERE id = :workerId LIMIT 1', $workerId)->fetch();

		if ($account['disabled'])
		{
			session_destroy();
			session_start();
			session_regenerate_id(true);

			// nie mam lepszego pomysłu
			setMessage('error::Twoje konto zostało dezaktywowane przez administratora.');
			$router->redirect('/account/login-form');
		}

		$permissions = $db->run('SELECT * FROM permissions WHERE worker_id = :workerId', $workerId)->fetch(); // zduplikowane w class worker po logowaniu

		array_shift($permissions);
		foreach ($permissions as $permission => $value)
		{
			$_SESSION['permission'][$permission] = $value;
		}

		$token = substr(sha1(rand() . 'token'), 0, 15); // jest zduplikowana do metody w class worker
		$values = [
			'token' => $token,
			'workerId' => $workerId,
		];
		$updateToken = $db->run('UPDATE workers SET security_token = :token WHERE id = :workerId', $values);

		if (!$updateToken) {
			session_destroy();
			session_start();
			session_regenerate_id(true);
			throw new Exception('ERR: update token failed.'); // dla testu
		}

		setToSession('workerSecurityToken', $token);
	}

	if (!getFromSession('trustedDevice') && $router->getModule() !== 'module/account.php')
	{
		$router->redirect('/account/register-device');
	}
}
else
{
	$workerId = unmaskWorkerId(getFromCookie('workerId'));
	$auth = checkAuth($db, getFromCookie('auth'));

	if ($workerId && $auth)
	{
		$worker = new Worker($db);
		$worker->setWorkerId($workerId);

		if ($worker->autologin())
		{
			if (isset($_SESSION['locationUrl']['previous']))
			{
				$redirect = $_SESSION['locationUrl']['previous'];
				// unset($_SESSION['locationUrl']['afterLogin']);
				$router->redirect($redirect);
			}
		}
	}
	else
	{
		setcookie('auth', '', 0, '/');
	}
}

if (!workerLoggedIn() && $router->getModule() !== 'module/account.php')
{
	setMessage('warn::Wymaganie zalogowanie się.');
	
	$_SESSION['locationUrl']['previous'] = $_SESSION['locationUrl']['this']; // zapakować w funkcję czy coś

	$router->redirect('/account/login-form/redir');
}

$view = new View;

$module = $router->getModule();
$action = $router->getAction();
$id = $router->getId();

require($module);