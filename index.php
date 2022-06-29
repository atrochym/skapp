<?php

require 'config.php';
require 'functions.php';

spl_autoload_register('classLoader');
session_start();

$controller = new Controller;
$db = new Database;
$view = new View;


$module = $controller->loadModule();
$action = $controller->action();
$id = $controller->id();

if (!$_SESSION['workerId'] && $module !== 'module/account.php')
{
	setMessage('warn::Wymaganie zalogowanie się.');
	
	$_SESSION['locationUrl']['afterLogin'] = $_SESSION['locationUrl']['this']; // zapakować w funkcję czy coś

	$controller->redirect('account/login-form/redir');
}

if(workerLoggedIn())
{
	$workerId = getFromSession('workerId');
	$values = ['workerId' => $workerId];
	$worker = $db->run('SELECT security_token FROM workers WHERE id = :workerId LIMIT 1', $values)->fetch();

	if ($worker['security_token'] != getFromSession('workerSecurityToken'))
	{
		$account = $db->run('SELECT is_disabled FROM workers WHERE id = :workerId LIMIT 1', $values)->fetch();

		if ($account['is_disabled'])
		{
			session_destroy();
			session_start();

			// nie mam lepszego pomysłu
			setMessage('error::Twoje konto zostało dezaktywowane przez administratora.');
			$controller->redirect('account/login-form');
		}

		$permissions = $db->run('SELECT * FROM permissions WHERE worker_id = :workerId', $values)->fetch(); // zduplikowane w class worker po logowaniu

		array_shift($permissions);
		foreach ($permissions as $permission => $value)
		{
			$_SESSION['permission'][$permission] = $value;
		}

		$token = substr(md5(rand() . 'token'), 0, 10); // jest zduplikowana do metody w class worker
		$values = [
			'token' => $token,
			'workerId' => $workerId,
		];
		$updateToken = $db->run('UPDATE workers SET security_token = :token WHERE id = :workerId', $values);

		if (!$updateToken) {
			session_destroy();
			session_start();
			throw new Exception('ERR: update token failed.'); // dla testu
		}

		$_SESSION['workerSecurityToken'] = $token;
	}
}
v('s');
require($module);