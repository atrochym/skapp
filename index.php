<?php

require 'config.php';

if (!str_contains($_SERVER['REQUEST_URI'], 'json')) {

	error_reporting(E_ALL);
	ini_set('display_errors', 1);
}

function debugMode() {
	// dla v() ve() e() i PDO
	$debugMode = DEBUG;

	if (str_contains($_SERVER['REQUEST_URI'], 'json'))
		return false;

	return $debugMode;
}

session_start();


// // ===  do wywalenia bo jest w kontrolerze, ale jeszcze używana
// function redirect($destination) {
// 	header('Location: ' . DIR . $destination);
// 	exit;
// }

function classLoader($className) {
	// $className = strtolower($className);
	$classFile = "class/$className.class.php";

	if (!file_exists($classFile)) {
		throw new Exception('classLoader: missing class '.$className);
	}
	require_once($classFile);
	return;
}

spl_autoload_register('classLoader');

require 'functions.php';



$controller = new Controller;
$db = new Database;
$model = new Model($db);
$view = new View($model->getData());


$module = $controller->loadModule();
$action = $controller->action();
$id = $controller->id();

if (!$_SESSION['workerId'] && $module !== 'module/account.php')
{
	$model->message->set([
		'messageContent' => 'Wymaganie zalogowanie się.',
		'messageType' => '']
	);
	
	$_SESSION['locationUrl']['afterLogin'] = $_SESSION['locationUrl']['this'];

	$controller->redirect('account/login-form/redir');
}

require($module);