<?php

class SettingsModel {

	private $model;
	// private $device;
	// private $deviceId;

	public function __construct(Model $model) {

		$this->model = $model;
	}

	private function getWorkers() {
		$workers = $this->model->db->query('SELECT * FROM workers');
		$workers = $workers->fetchAll();

		return $workers;
	}

	private function getWorkerPermissions(int $workerId) {
		$permissions = $this->model->db->query("SELECT * FROM permissions WHERE worker_id = $workerId LIMIT 1");
		$permissions = $permissions->fetch();
		array_shift($permissions);

		return $permissions;
	}

	// public function index(array $data) {
	public function index() {
		
		$data['workers'] = $this->getWorkers();
		$data['permissions'] = $this->getWorkerPermissions($this->model->get('workerId'));

		return $data;
	}
}