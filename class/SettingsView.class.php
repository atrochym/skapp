<?php

class SettingsView {
	private $view;

	public function __construct(View $view) {

		$this->view = $view;
	}

	public function index(array $data) {

		$this->view->joinJS('settings');

		foreach ($data['workers'] as $key => $worker) {

			if ($worker['is_disabled']) {
				$manage = 'Odblokuj';
				$manageUrl = 'enable';
			} else {
				$manage = 'Zablokuj';
				$manageUrl = 'disable';
			}
			
			$data['workers'][$key]['manage'] = $manage;
			$data['workers'][$key]['manageUrl'] = $manageUrl;

			$data['workers'][$key]['status'] = $worker['is_activated'] ? 'Aktywne' : 'Nieaktywne';
		}

		$this->view->addData($data);
		$this->view->addView('settings');
	}
}