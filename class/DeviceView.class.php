<?php

class DeviceView {
	private $view;

	public function __construct(View $baseView) {

		$this->view = $baseView;
		$this->view->joinCSS('receive');
		$this->view->joinCSS('device');
		$this->view->joinJS('device');
	}

	public function device(array $data) { // do wywalenia

		$this->view->addData($data);
		$this->view->addView('receive-create');
	}

	public function deviceDetails(array $data)
	{
		$this->view->addData($data);
		$this->view->addView('receive-create');
	}
	
	public function devicesList(array $devices) {

		$this->view->addData($devices);
		$this->view->addView('customer-devices-list');
	}

	public function createForm(array $data) {

		$this->view->addData($data);
		$this->view->addView('device-create-form');

	}
}

?>