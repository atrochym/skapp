<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

// $model = new Model();
// $view = new View($model->getData());

// $urlParser = new UrlParser;
// $action = $controller->action();

if ($action == 'redir') {

	$accountView = new AccountView($view);

	$model->message->set(['messageContent' => 'Wymagane zalogowanie się.',
						 'messageType' => '']);

	$accountView->loginForm();
	$view->renderSingle();

}
elseif ($action == 'login')
{
	if (workerLoggedIn())
	{
		redirect('desktop');
	}

	$validate = new Validate;
	$worker = new Worker($db, $validate);
	$accountView = new AccountView($view);

	formBackup();
	// $data = [
	// 	'workerLogin' => $_POST['worker_login'],
	// 	'workerPassword' => $_POST['worker_password']
	// ];

	$worker->setLogin($_POST['worker_login']);
	$worker->setPassword($_POST['worker_password']);

	if (!$worker->login())
	{
		$view->addData([
			'message' => $worker->getMessage()
		]);
	}


	// $result = $worker->login();
	// $model->message->set($result);

	// if (!$result['success']) {

		// $controller->redirect('back');
	// }
	
	// a może sprawdzić po /redir/ z url?
	if (isset($_SESSION['locationUrl']['afterLogin'])) {
		$redirect = $_SESSION['locationUrl']['afterLogin'];
		unset($_SESSION['locationUrl']['afterLogin']);
		$controller->redirect($redirect);
	}
	
	$controller->redirect('receive/new');


} elseif ($action == 'login-form') {

	// $accountModel = new AccountModel($model);
	$accountView = new AccountView($view);

	if (workerLoggedIn()) {
		redirect('desktop');
	}

	$accountView->loginForm();
	$view->renderSingle();


}
elseif ($action == 'logout')
{
	if (!workerLoggedIn())
	{
		redirect('account/login-form');
	}

	$validate = new Validate;
	$account = new Account($db, $validate);
	$account->logout();
	$model->message->set(['messageContent' => $account->message]);
	$controller->redirect('account/login-form');

}
elseif ($action == 'reset-password')
{
	if (!workerLoggedIn())
	{
		ve('zaloguj sie');
		redirect('desktop');
	}

	$validate = new Validate;
	$account = new Account($db, $validate);


	// $accountModel = new AccountModel($model);
	$accountView = new AccountView($view);

	$workerId = $controller->id();

	$result = $account->resetPassword($workerId);

	$model->message->set(['messageContent' => $account->message]);

	$controller->redirect('back');

} elseif ($action == 'proceed-password') {

	if (workerLoggedIn()) {
		redirect('desktop');
	}
	$validate = new Validate;
	$account = new Account($db, $validate);

	$accountModel = new AccountModel($model);
	$accountView = new AccountView($view);

	$data = [
		'urlLogin' => trim($controller->paramNumber(1)),
		'urlToken' => trim($controller->paramNumber(2))
	];

	$result = $account->proceedResetPassword($data);

	if (!$result)
	{
		// $view->addData(['message' => $account->message]); // jak ogarnę komunikaty
		$model->message->set(['messageContent' => $account->message]);
		$controller->redirect('account/login-form');

	}

	$data = [
		'XworkerId' => $account->worker['id'],
		'XworkerName' => $account->worker['name'],
		'XworkerLogin' => $account->worker['login'],
		'token' =>  $account->worker['token']
	];

	$accountView->proceedPasswordForm($data);
	$view->renderSingle();

}
elseif ($action == 'password-change')
{
	if (workerLoggedIn()) {
		redirect('desktop');
	}
	$validate = new Validate;
	$account = new Account($db, $validate);

	// $accountModel = new AccountModel($model);
	// $result = $accountModel->passwordChange($_POST);

	$result = $account->passwordChange($_POST);

	$model->message->set(['messageContent' => $account->message]);

	if (!$result) {
		$controller->redirect('back');
	}

	$controller->redirect('account/login-form');

} elseif ($action == 'create')
{
	formBackup();

	$validate = new Validate;
	$account = new Account($db, $validate);

	$account->setFullName($_POST['name']);
	$account->setEmail($_POST['email']);
	$account->create();

	// $view->addData(['message' => $account->message]);
	$model->message->set(['messageContent' => $account->message]);
	$controller->redirect('back');


} elseif ($action == 'proceed-register')
{
	if (workerLoggedIn())
	{
		redirect('desktop');
	}

	$validate = new Validate;
	$account = new Account($db, $validate);
	$accountView = new AccountView($view);

	$data = [
		'urlName' => trim($controller->paramNumber(1)),
		'urlToken' => trim($controller->paramNumber(2))
	];

	if (!$account->proceedRegister($data))
	{
		// $view->addData('message', $account->message); //użytć tego jak już ujednolice komunikaty
		$model->message->set(['messageContent' => $account->message]);
		$controller->redirect('account/login-form');
	}

	$data = [
		'XworkerId' => $account->worker['id'],
		'XworkerName' => $account->worker['name'],
		'token' => $account->worker['token'],
	];

	$accountView->proceedRegisterForm($data);
	$view->renderSingle();

} elseif ($action == 'create-password') {

	if (workerLoggedIn()) {
		redirect('desktop');
	}
	
	formBackup();

	$validate = new Validate;
	$account = new Account($db, $validate);

	$result = $account->createPassword($_POST);
	$model->message->set(['messageContent' => $account->message]);
	// $view->addData('message', $account->message); //użytć tego jak już ujednolice komunikaty


	if (!$result)
	{
		$controller->redirect('back');
	}

	$controller->redirect('account/login-form');


} elseif ($action == 'disable') {
	$workerId = $controller->id();

	$accountModel = new AccountModel($model);
	$result = $accountModel->disableAccount($workerId);
	$model->message->set($result);
	$controller->redirect('back');


}  elseif ($action == 'enable') {
	$workerId = $controller->id();

	$accountModel = new AccountModel($model);
	$result = $accountModel->enableAccount($workerId);
	$model->message->set($result);
	$controller->redirect('back');

} elseif ($action == 'edit') {
	echo '<br><br> TODO edit account worker';
}

// ivybe.ddns.net/sk/account/proceed-register/nazwiskoiimie/0d4bd044b12654e