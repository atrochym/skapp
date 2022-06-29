<?php

class Controller {

	// private $model;
	// private $view;
	// private $request;

	private array $urlParams;
	private string $module = '';
	private string $action = '';
	private int $id = 0;
	private array $restParams = array();	


	public function __construct() {
		
		// $this->model = new Model;
		// $this->view = new View($this->model->getData());
		$this->urlParser();

		$urlPath = substr($_SERVER['PATH_INFO'], 1);

		if ($_SESSION['locationUrl']['this']) {
			$_SESSION['locationUrl']['previous'] = $_SESSION['locationUrl']['this'];
		}
		$_SESSION['locationUrl']['this'] = $urlPath;

		v('s');
		// exit;

	}

	private function urlParser() {

		$urlPath = trim($_SERVER['PATH_INFO'], '/');
		$this->urlParams = explode('/', $urlPath);

		$this->module = $this->urlParams[0];
		array_shift($this->urlParams);

		foreach ($this->urlParams as $param) {

			if (ctype_alpha(str_replace('-', '', $param)) && !$this->action) {
				$this->action = $param;
				continue;
			}

			if (ctype_digit($param) && !$this->id) {
				$this->id = $param;
				continue;
			}

			if (str_contains($param, '-')) {
				$param = explode('-', $param);
				if (!ctype_alnum($param[0]) || !ctype_alnum($param[1])) {
					continue;
				}
				$this->restParams[$param[0]] = $param[1];
			}
		}
	}

	public function redirect($destination)
	{
		if ($destination == 'back')
		{
			header('Location: ' . DIR . $_SESSION['locationUrl']['previous']);
			exit;
		}

		header('Location: ' . DIR . $destination);
		exit;
	}

	public function loadModule() {

		$modulePath = ctype_alpha($this->module) ? "module/$this->module.php" : 'home';
	
		if (!file_exists($modulePath)) {
			exit('ERR controller: missing module');
			// http_response_code(404);
			// exit;
		}

		return $modulePath;
	}

	public function action() {

		return $this->action;
	}

	public function id() {

		return $this->id;
	}

	public function param($paramName) {

		if (isset($this->restParams[$paramName])) {
			return $this->restParams[$paramName];
		} else {
			exit("param $paramName uregistered. <br><br>");
		}
	}

	function paramNumber($number) {
		if (isset($this->urlParams[$number])) {
			return $this->urlParams[$number];
		} else {
			exit("param number $number uregistered. <br><br>");
		}
	}

}