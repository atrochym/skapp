<?php

class PartView {
	private $view;
	
	public function __construct(View $baseView)
	{

		$this->view = $baseView;
		$this->view->joinCSS('part');
		$this->view->joinJS('part');
		// $this->view->joinJS('device');
	}

	public function createOrder($data) {

		$this->view->addData($data);
		$this->view->addView('create-order-form');
		$this->view->addView('category-create');
	}
}

?>