<?php

class ListView {
	private $view;

	public function __construct(View $baseView) {

		$this->view = $baseView;
		// $this->view->joinCSS('receive');
		$this->view->joinCSS('list');
		// $this->view->joinJS('device');
	}

	public function listIndex(array $data) {



		$this->view->addData($data);
		$this->view->addView('list-index');
	}
}

?>