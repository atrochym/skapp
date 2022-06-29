<?php

class ReceiveView {
	private $view;
	
	public function __construct(View $baseView)	{

		$this->view = $baseView;
		$this->view->joinCSS('receive');
		$this->view->joinCSS('device');
		$this->view->joinJS('device'); // posprzątać, wywalić
		$this->view->joinJS('receive');
	}

	public function testReceive(array $data) {

		$testWorkersList = $data['workersList'];
		$data['actionButtons'] = true;

		foreach ($data['services'] as $key => $service)	{

			$data['services'][$key]['workerName'] = $testWorkersList[$service['worker_id']];
		}

		if ('finished' == $data['receiveStatus'] || 'finished' == $data['receiveStatus'] || $data['receiveDeleted']) {
			
			$data['actionButtons'] = false;
		}

		$this->view->addData($data);
		$this->view->addView('receive-fix-list');
	}

	public function relatedReceives(array $data) {

		//exception...
		// $data['finished'] = $data['finished'] ?: '-';

		foreach ($data['relatedReceives'] as $key => $receive) {

			if ($data['receiveId_'] == $receive['id']) {

				unset($data['relatedReceives'][$key]);
			}
		}

		$this->view->addData($data);
		$this->view->addView('receive-related-fix');
	}

	public function info(array $data) {

		$data['issue'] = $data['issue'] ?: '---';
		$this->view->addData($data);
		$this->view->addView('receive-test-0');
	}

	public function parts(array $data) {

		$this->view->addView('receive-parts');
		$this->view->addData(['parts' => $data]);
	}

	public function comments(array $data) {

		$this->view->addView('receive-comments');
		$this->view->addData(['commentsCount' => count($data)]);
		$this->view->addData(['comments' => $data]);
	}

	public function menu(array $data) {

		$data['password'] = $data['password'] ?: '-';
		
		$this->view->addData($data);
		$this->view->addView('receive-top-menu');
	}

	public function addCustomer() {

		$this->view->addView('receive-add-customer');
	}

	public function receivesList(array $data) {

		$this->view->addData($data);
		$this->view->addView('customer-receives');
	}


	public function partAssignForm() {
		
		$this->view->addView('receive-assign-part');

	}
}

?>