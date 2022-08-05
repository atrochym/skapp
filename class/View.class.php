<?php

class View
{
	private $message;
	private $views = [];
	private $renderData = [];
	private $resourcesJS = [];
	private $resourcesCSS = [];


	public function __construct()
	{
		if(workerLoggedIn())
		{
			$this->addData(['workerName' => getFromSession('workerName')]);
		}
	}


	public function addCSS($resource)
	{
		$this->resourcesCSS[] = $resource;
	}
	
	public function addJS($resource, $module = false)
	{
		$resource = ['file' => $resource];
		$resource += $module ? ['module' => 'type="module"'] : ['module' => null];
		$this->resourcesJS[] = $resource;
	}

	public function renderSingle()
	{
		$message = getMessage(); // zduplikowane, przemyśleć

		if ($message)
		{
			$message = explode('::', $message);
			$this->addData([
				'message' => $message[1]
			]);
		}

		extract($this->renderData);
		$resource = 'templates/'.$this->views[0].'.phtml';
		require_once ($resource);
		exit;
	}
	
	public function setMessage(string $message)
	{
		$message = explode('::', $message);

		$this->addData([
			'messageType' => $message[0],
			'messageContent' => $message[1]
		]);
	}

	public function addView(string $view) {
		$this->views[] = $view;
	}

	public function render()
	{
		$resourcesCSS = $this->resourcesCSS;
		$resourcesJS = $this->resourcesJS;
		// $message = $this->message->show();
		$siteTitle = ['siteTitle' => 'Studio-Komp'];

		$this->addData($siteTitle);
		// $this->addData(['message' => getMessage()]);
		$message = getMessage();

		if ($message)
		{
			$this->setMessage($message);
		}
		
		extract($this->renderData);

		require_once ('templates/header.phtml');
		require_once ('templates/sidebar.phtml');
		require_once ('templates/message.phtml');

		foreach ($this->views as $view)
		{
			require_once ("templates/$view.phtml");
		}

		require_once ('templates/footer.phtml');
		exit;
	}

	// public function set(array $data) {
	// 	if (array_intersect_key($data, $this->data)) {
	// 		throw new Exception('ERR model: data key exist');
	// 	}

	// 	$this->data = array_merge($this->data, $data);
	// }

	public function addData(array $renderData)
	{
		if (array_intersect_key($this->renderData, $renderData))
		{
			throw new Exception('View :: render > data key exist :: ' . v($this->renderData) . v($renderData));
		}

		$this->renderData = array_merge($this->renderData, $renderData);
	}

	// public function showMessage(string $message)
	// {
	// 	$this->addData([
	// 		'message' => $message,
	// 	]);
	// }
}

?>