<?php

class DesktopView {
	private $view;
	
	public function __construct(View $baseView) {

		$this->view = $baseView;
		// $this->view->joinCSS('receive');
		// $this->view->joinCSS('device');
		// $this->view->joinJS('device');
	}

	public function index() {

		$this->view->addView('desktop-index');
	}
}