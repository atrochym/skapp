<?php

class Settings
{
	private $model;
	// private $device;
	// private $deviceId;

	public function __construct(private Database $db)
	{}

	private function getWorkers()
	{
		$workers = $this->db->run('SELECT * FROM workers')->fetchAll();

		return $workers;
	}

	private function getWorkerPermissions(int $workerId) 
	{
		$permissions = $this->db->run('SELECT * FROM permissions WHERE worker_id = :workerId LIMIT 1', $workerId)->fetch();
		array_shift($permissions);

		return $permissions;
	}

	// public function index(array $data) {
	public function index()
	{
		// tak narazie
		$permissionsNames = $this->db->run('SELECT * FROM permissions_names')->fetchAll(PDO::FETCH_KEY_PAIR);

		$data['workers'] = $this->getWorkers();
		$data['permissions'] = $this->getWorkerPermissions(getFromSession('workerId'));
		$data['permissionsNames'] = $permissionsNames;

		return $data;
	}

	function getWorkersList() // chyba nie chcę tego tutaj
	{
		if (isset($_SESSION['workersList']))
		{
			return $_SESSION['workersList'];
		}

		$workers = $this->db->run('SELECT id, name FROM workers')->fetchAll(PDO::FETCH_KEY_PAIR);
		$_SESSION['workersList'] = $workers;

		return $_SESSION['workersList'];
	}
}