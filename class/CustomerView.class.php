<?php

class CustomerView {

	private $view;
	
	public function __construct(View $view) {

		$this->view = $view;
		$this->view->joinCSS('receive');
		$this->view->joinCSS('device');
		// $this->view->joinJS('device');
	}

	public function render() {

		$this->view->render();
	}

	public function form() {
		$this->view->addView('receive-add-customer');
	}

	public function devices(array $data) {
		$data['name'] = $data['name'] ?: '-';
		$data['telephone'] = $data['telephone'] ?: '-';
		$data['email'] = $data['email'] ?: '-';
		$data['nonPolish'] = $data['nonPolish'] ? 'Tak' : '-';

		foreach ($data['devices'] as $device) {
			if ($device['receive_id']) {
				$data['receiveList'] = true;
				break;
			}
		}

		$this->view->addData($data);
		$this->view->addView('receive');
	}

	public function editForm(array $data) {

		$data['checkbox'] = $data['nonPolish'] ? 'checked' : '';

		$this->view->addData($data);
		$this->view->addView('customer-edit');

	}

	public function info() {
		// $this->view->addData($data);
		$this->view->addView('customer-info');
	}

	public function createForm() {

		$this->view->addView('customer-create-form');
	}

	public function customerDetails(array $data) {
		$data = $data['customer'];
		$data['nonPolish'] = $data['non_polish'] ? 'Tak' : '-';
		$data['email'] = $data['email'] ?: '-';
		$data['name'] = $data['name'] ?: '-';

		$this->view->addData($data);
		$this->view->addView('customer-details');
	}

	public function customerList(array $data) {
		$customers = $data['customers'];
		$customers = $customers ?: [];

		$this->view->addData(['customers' => $customers]);
		$this->view->addView('customer-list');
	}
}

?>