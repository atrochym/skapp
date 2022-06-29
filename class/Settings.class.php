<?php

class Settings {

	private $model;
	// private $device;
	// private $deviceId;

	public function __construct(private Database $db)
	{}

	private function getWorkers()
	{
		$workers = $this->db->run('SELECT * FROM workers', [])->fetchAll();

		return $workers;
	}

	private function getWorkerPermissions(int $workerId) 
	{
		$values = [
			'workerId' => $workerId,
		];
		$permissions = $this->db->run('SELECT * FROM permissions WHERE worker_id = :workerId LIMIT 1', $values)->fetch();
		array_shift($permissions);

		return $permissions;
	}

	// public function index(array $data) {
	public function index()
	{
		$data['workers'] = $this->getWorkers();
		$data['permissions'] = $this->getWorkerPermissions(getFromSession('workerId'));

		return $data;
	}

	function getWorkersList() // chyba nie chcę tego tutaj
	{
		if (isset($_SESSION['workersList']))
		{
			return $_SESSION['workersList'];
		}

		$workers = $this->db->run('SELECT id, name FROM workers', [])->fetchAll(PDO::FETCH_KEY_PAIR);
		$_SESSION['workersList'] = $workers;

		return $_SESSION['workersList'];
	}
}