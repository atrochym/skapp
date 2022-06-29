<?php

class AccountView {
	private $view;

	public function __construct(View $view) {

		$this->view = $view;
	}

	public function redirectLogin() {
		$this->view->addView('account-login-form');

	}

	public function proceedPasswordForm(array $data) {
		$this->view->addData($data);
		$this->view->addView('account-reset-password-form');
	}

	public function loginForm() {
		$this->view->addView('account-login-form');
	}

	public function proceedRegisterForm(array $data) {
		$this->view->addData($data);
		$this->view->addView('account-create-password-form');
		
	}
}